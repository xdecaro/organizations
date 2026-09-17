<?php

$root = dirname(__DIR__);
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migrationPath = $root . '/component/admin/sql/updates/mysql/1.0.28.sql';
$migration = is_file($migrationPath) ? (string) file_get_contents($migrationPath) : '';
$form = (string) file_get_contents($root . '/component/admin/forms/organization.xml');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationsModel.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organizations/default.php');
$provider = (string) file_get_contents($root . '/component/admin/src/Service/OrganizationProviderService.php');
$core = (string) file_get_contents($root . '/component/admin/src/Service/CoreIntegrationService.php');

$schemaFragments = [
    '`structure_level` VARCHAR(32) NOT NULL DEFAULT \'unspecified\'',
    '`territory_type` VARCHAR(32) DEFAULT NULL',
    '`territory_name` VARCHAR(190) DEFAULT NULL',
    '`operational_status` VARCHAR(32) NOT NULL DEFAULT \'active\'',
    '`status_since` DATE DEFAULT NULL',
    '`autonomy_legal` TINYINT(1) NOT NULL DEFAULT 0',
    '`autonomy_management` TINYINT(1) NOT NULL DEFAULT 0',
    '`autonomy_administrative` TINYINT(1) NOT NULL DEFAULT 0',
    '`autonomy_tax` TINYINT(1) NOT NULL DEFAULT 0',
    '`autonomy_fiscal` TINYINT(1) NOT NULL DEFAULT 0',
    'KEY `idx_org_structure_level` (`structure_level`)',
    'KEY `idx_org_operational_status` (`operational_status`)',
];

foreach ($schemaFragments as $needle) {
    if (!str_contains($install, $needle) || !str_contains($migration, $needle)) {
        fwrite(STDERR, "Missing institutional schema fragment: {$needle}\n");
        exit(1);
    }
}

$formFragments = [
    '<fieldset name="structure"',
    'name="structure_level"',
    'name="territory_type"',
    'name="territory_name"',
    'name="operational_status"',
    'name="status_since"',
    'name="autonomy_legal"',
    'name="autonomy_management"',
    'name="autonomy_administrative"',
    'name="autonomy_tax"',
    'name="autonomy_fiscal"',
];

foreach ($formFragments as $needle) {
    if (!str_contains($form, $needle)) {
        fwrite(STDERR, "Missing institutional form fragment: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($model, "'structure_level'")
    || !str_contains($model, "'operational_status'")
    || !str_contains($template, "FIELD_STRUCTURE_LEVEL")
    || !str_contains($template, "FIELD_OPERATIONAL_STATUS")) {
    fwrite(STDERR, "Organizations list must expose structure level and operational status.\n");
    exit(1);
}

foreach ([
    "'o.structure_level'",
    "'o.territory_type'",
    "'o.territory_name'",
    "'o.operational_status'",
    "'o.status_since'",
    "'o.autonomy_legal'",
    "'o.autonomy_management'",
    "'o.autonomy_administrative'",
    "'o.autonomy_tax'",
    "'o.autonomy_fiscal'",
] as $needle) {
    if (!str_contains($provider, $needle)) {
        fwrite(STDERR, "Organizations provider must expose institutional field: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($core, "organizations.institutional_profile")) {
    fwrite(STDERR, "Core capability organizations.institutional_profile is required.\n");
    exit(1);
}

echo "Organizations institutional profile contract OK\n";
