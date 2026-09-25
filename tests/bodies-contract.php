<?php

$root = dirname(__DIR__);
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migrationPath = $root . '/component/admin/sql/updates/mysql/1.0.29.sql';
$migration = is_file($migrationPath) ? (string) file_get_contents($migrationPath) : '';
$edit = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$bodyTemplatePath = $root . '/component/admin/tmpl/organization/edit_bodies.php';
$bodyTemplate = is_file($bodyTemplatePath) ? (string) file_get_contents($bodyTemplatePath) : '';
$providerPath = $root . '/component/admin/src/Service/OrganizationBodiesService.php';
$provider = is_file($providerPath) ? (string) file_get_contents($providerPath) : '';
$core = (string) file_get_contents($root . '/component/admin/src/Service/CoreIntegrationService.php');

foreach ([
    '#__xdecaroorganizations_bodies',
    '`organization_id` INT UNSIGNED NOT NULL',
    '`parent_id` INT UNSIGNED DEFAULT NULL',
    '`body_type` VARCHAR(50) NOT NULL DEFAULT \'other\'',
    '`starts_on` DATE DEFAULT NULL',
    '`ends_on` DATE DEFAULT NULL',
    'UNIQUE KEY `idx_body_uuid` (`uuid`)',
    'KEY `idx_body_org` (`organization_id`)',
    'KEY `idx_body_parent` (`parent_id`)',
    'CONSTRAINT `fk_xdecaroorganizations_body_org`',
] as $needle) {
    if (!str_contains($install, $needle) || !str_contains($migration, $needle)) {
        fwrite(STDERR, "Missing bodies schema fragment: {$needle}\n");
        exit(1);
    }
}

foreach ([
    '`body_id` INT UNSIGNED DEFAULT NULL',
    'KEY `idx_appointment_body` (`body_id`)',
    'CONSTRAINT `fk_xdecaroorganizations_appointment_body`',
] as $needle) {
    if (!str_contains($install, $needle) || !str_contains($migration, $needle)) {
        fwrite(STDERR, "Appointments must support optional body linkage: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($edit, "'bodies'") || !str_contains($edit, "loadTemplate('bodies')")) {
    fwrite(STDERR, "Organization editor must include the Bodies tab.\n");
    exit(1);
}

foreach (['data-body-add', 'data-body-edit', 'body_type', 'organization_id'] as $needle) {
    if (!str_contains($bodyTemplate, $needle)) {
        fwrite(STDERR, "Bodies UI missing: {$needle}\n");
        exit(1);
    }
}

foreach (['getBodiesByOrganization', 'getBody'] as $needle) {
    if (!str_contains($provider, $needle)) {
        fwrite(STDERR, "Bodies provider missing method: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($core, 'organizations.bodies')) {
    fwrite(STDERR, "Core capability organizations.bodies is required.\n");
    exit(1);
}

echo "Organizations bodies contract OK\n";
