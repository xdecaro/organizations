<?php

$root = dirname(__DIR__);
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migrationPath = $root . '/component/admin/sql/updates/mysql/1.0.30.sql';
$migration = is_file($migrationPath) ? (string) file_get_contents($migrationPath) : '';
$edit = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$templatePath = $root . '/component/admin/tmpl/organization/edit_delegations.php';
$template = is_file($templatePath) ? (string) file_get_contents($templatePath) : '';
$servicePath = $root . '/component/admin/src/Service/OrganizationDelegationsService.php';
$service = is_file($servicePath) ? (string) file_get_contents($servicePath) : '';
$core = (string) file_get_contents($root . '/component/admin/src/Service/CoreIntegrationService.php');
$appointmentModel = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAppointmentModel.php');

foreach ([
    '#__xdecaroorganizations_delegations',
    '`organization_id` INT UNSIGNED NOT NULL',
    '`appointment_id` INT UNSIGNED NOT NULL',
    '`title` VARCHAR(190) NOT NULL',
    '`scope` TEXT DEFAULT NULL',
    '`starts_on` DATE NOT NULL',
    '`ends_on` DATE DEFAULT NULL',
    'KEY `idx_delegation_org` (`organization_id`)',
    'KEY `idx_delegation_appointment` (`appointment_id`)',
    'CONSTRAINT `fk_xdecaroorganizations_delegation_org`',
    'CONSTRAINT `fk_xdecaroorganizations_delegation_appointment`',
    'ON DELETE RESTRICT',
] as $needle) {
    if (!str_contains($install, $needle) || !str_contains($migration, $needle)) {
        fwrite(STDERR, "Missing delegations schema fragment: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($edit, "'delegations'") || !str_contains($edit, "loadTemplate('delegations')")) {
    fwrite(STDERR, "Organization editor must include the Delegations tab.\n");
    exit(1);
}

foreach (['data-delegation-add', 'data-delegation-edit', 'appointment_id', 'organization_id'] as $needle) {
    if (!str_contains($template, $needle)) {
        fwrite(STDERR, "Delegations UI missing: {$needle}\n");
        exit(1);
    }
}

foreach (['getDelegationsByOrganization', 'getDelegation'] as $needle) {
    if (!str_contains($service, $needle)) {
        fwrite(STDERR, "Delegations provider missing method: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($core, 'organizations.delegations')) {
    fwrite(STDERR, "Core capability organizations.delegations is required.\n");
    exit(1);
}

if (!str_contains($appointmentModel, 'hasDelegations')) {
    fwrite(STDERR, "Deleting an appointment with delegations must be guarded before the database constraint.\n");
    exit(1);
}

echo "Organizations delegations contract OK\n";
