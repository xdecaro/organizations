<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$cssPath = $root . '/component/media/css/maintenance.css';
$viewPath = $root . '/component/admin/src/View/Maintenance/HtmlView.php';
$backupPath = $root . '/component/admin/src/Service/BackupService.php';
$versionPath = $root . '/VERSION';
$manifestPath = $root . '/component/xdecaroorganizations.xml';
$packagePath = $root . '/package/pkg_organizations.xml';

foreach ([$cssPath, $viewPath, $backupPath, $versionPath, $manifestPath, $packagePath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing required file: {$path}\n");
        exit(1);
    }
}

$css = (string) file_get_contents($cssPath);
$view = (string) file_get_contents($viewPath);
$backup = (string) file_get_contents($backupPath);
$version = trim((string) file_get_contents($versionPath));
$manifest = (string) file_get_contents($manifestPath);
$package = (string) file_get_contents($packagePath);

if (version_compare($version, '1.2.18', '<')
    || !str_contains($manifest, '<version>' . $version . '</version>')
    || !str_contains($package, '<version>' . $version . '</version>')) {
    fwrite(STDERR, "Organizations maintenance polish requires version 1.2.18 or later with aligned manifests.\n");
    exit(1);
}

foreach (['.xdecaro-maintenance-page {', 'max-width: 100%', 'min-width: 0', '.xdecaro-maintenance-page .table-responsive', 'overflow-x: auto'] as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "Maintenance overflow containment missing: {$needle}.\n");
        exit(1);
    }
}

if (str_contains($css, 'min-width: 20rem')) {
    fwrite(STDERR, "Maintenance actions column still forces a 20rem minimum width.\n");
    exit(1);
}

foreach (['backupReady', '#__xdecaroorganizations_backups', '#__xdecaroorganizations_maintenance_log'] as $needle) {
    if (!str_contains($view, $needle)) {
        fwrite(STDERR, "Backup readiness must include maintenance database availability: {$needle}.\n");
        exit(1);
    }
}

if (str_contains($backup, "getParam('timezone'")) {
    fwrite(STDERR, "Backup filename must not be forced by a possibly stale user timezone preference.\n");
    exit(1);
}
if (!str_contains($backup, "get('offset', 'UTC')")) {
    fwrite(STDERR, "Backup filename must use Joomla configured timezone.\n");
    exit(1);
}

if (!str_contains($backup, "SCHEMA_VERSION = '1.2.18'")) {
    fwrite(STDERR, "Backup schema version must remain aligned to the unchanged 1.2.18 database schema.\n");
    exit(1);
}

echo "Organizations maintenance polish contract OK ({$version})\n";
