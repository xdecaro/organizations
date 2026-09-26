<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$requiredFiles = [
    'schema' => 'component/admin/src/Service/DatabaseSchemaDefinition.php',
    'inspector' => 'component/admin/src/Service/DatabaseSchemaInspector.php',
    'database' => 'component/admin/src/Service/DatabaseMaintenanceService.php',
    'storage' => 'component/admin/src/Service/BackupStorageService.php',
    'backup' => 'component/admin/src/Service/BackupService.php',
    'restore' => 'component/admin/src/Service/RestoreService.php',
    'log' => 'component/admin/src/Service/MaintenanceLogService.php',
    'controller' => 'component/admin/src/Controller/MaintenanceController.php',
    'view' => 'component/admin/src/View/Maintenance/HtmlView.php',
    'template' => 'component/admin/tmpl/maintenance/default.php',
    'javascript' => 'component/media/js/database-maintenance.js',
];

foreach ($requiredFiles as $label => $relative) {
    if (!is_file($root . '/' . $relative)) {
        fwrite(STDERR, "Missing {$label}: {$relative}\n");
        exit(1);
    }
}

$schema = (string) file_get_contents($root . '/' . $requiredFiles['schema']);
$database = (string) file_get_contents($root . '/' . $requiredFiles['database']);
$controller = (string) file_get_contents($root . '/' . $requiredFiles['controller']);
$template = (string) file_get_contents($root . '/' . $requiredFiles['template']);
$access = (string) file_get_contents($root . '/component/admin/access.xml');
$manifest = (string) file_get_contents($root . '/component/xdecaroorganizations.xml');
$installSql = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');

$functional = [
    '#__xdecaroorganizations_organizations',
    '#__xdecaroorganizations_bodies',
    '#__xdecaroorganizations_appointments',
    '#__xdecaroorganizations_delegations',
    '#__xdecaroorganizations_affiliations',
];
$maintenance = [
    '#__xdecaroorganizations_backups',
    '#__xdecaroorganizations_maintenance_log',
];

foreach (array_merge($functional, $maintenance) as $table) {
    if (!str_contains($schema, $table)) {
        fwrite(STDERR, "Canonical schema missing {$table}.\n");
        exit(1);
    }
    if (!str_contains($installSql, $table)) {
        fwrite(STDERR, "Installer SQL missing {$table}.\n");
        exit(1);
    }
}

foreach ($functional as $table) {
    if (!str_contains($database, $table)) {
        fwrite(STDERR, "Maintenance whitelist missing {$table}.\n");
        exit(1);
    }
}
foreach ($maintenance as $table) {
    if (preg_match('/FUNCTIONAL_TABLES\s*=.*' . preg_quote($table, '/') . '/s', $database) === 1) {
        fwrite(STDERR, "Maintenance table must not be destructive functional data: {$table}.\n");
        exit(1);
    }
}

foreach (['organizations.backup', 'organizations.restore', 'organizations.database_repair', 'organizations.database_destructive'] as $action) {
    if (!str_contains($access, 'name="' . $action . '"')) {
        fwrite(STDERR, "ACL action missing: {$action}.\n");
        exit(1);
    }
}

foreach (['SVUOTA', 'RICREA'] as $confirmation) {
    if (!str_contains($controller, $confirmation) || !str_contains($template, $confirmation)) {
        fwrite(STDERR, "Typed confirmation missing: {$confirmation}.\n");
        exit(1);
    }
}

if (!str_contains($manifest, '<menu view="maintenance">')) {
    fwrite(STDERR, "Maintenance submenu missing.\n");
    exit(1);
}

foreach (['createBackup', 'verifyBackup', 'downloadBackup', 'deleteBackup', 'previewRestore', 'restoreFull', 'checkDatabase', 'repairDatabase', 'emptyDatabase', 'recreateDatabase'] as $method) {
    if (!str_contains($controller, 'function ' . $method . '(')) {
        fwrite(STDERR, "Maintenance controller method missing: {$method}.\n");
        exit(1);
    }
}

if (preg_match('/DROP\s+TABLE[^;]*(?:LIKE|%|xdecaroorganizations_\*)/i', $database)) {
    fwrite(STDERR, "Broad destructive DROP detected.\n");
    exit(1);
}

if (!str_contains($template, 'Membership') || !str_contains($template, 'Competitions')) {
    fwrite(STDERR, "External-reference warning missing.\n");
    exit(1);
}

echo "Organizations 1.2.17 database maintenance contract OK\n";
