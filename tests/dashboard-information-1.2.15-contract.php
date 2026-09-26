<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$requiredFiles = [
    $root . '/component/admin/src/Model/DashboardModel.php',
    $root . '/component/admin/src/Model/InformationModel.php',
    $root . '/component/admin/tmpl/dashboard/default.php',
    $root . '/component/admin/tmpl/information/default.php',
    $root . '/component/media/css/admin.css',
    $root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini',
    $root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini',
];

foreach ($requiredFiles as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing dashboard/information file: {$path}\n");
        exit(1);
    }
}

$dashboardModel = (string) file_get_contents($root . '/component/admin/src/Model/DashboardModel.php');
$dashboardLayout = (string) file_get_contents($root . '/component/admin/tmpl/dashboard/default.php');
$informationModel = (string) file_get_contents($root . '/component/admin/src/Model/InformationModel.php');
$informationLayout = (string) file_get_contents($root . '/component/admin/tmpl/information/default.php');
$css = (string) file_get_contents($root . '/component/media/css/admin.css');
$it = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$en = (string) file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini');

foreach ([
    '#__xdecaroorganizations_organizations',
    '#__xdecaroorganizations_bodies',
    '#__xdecaroorganizations_appointments',
    '#__xdecaroorganizations_delegations',
    '#__xdecaroorganizations_affiliations',
] as $table) {
    if (!str_contains($dashboardModel, $table)) {
        fwrite(STDERR, "Dashboard model must aggregate {$table}.\n");
        exit(1);
    }
    if (!str_contains($informationModel, $table)) {
        fwrite(STDERR, "Information diagnostics must check {$table}.\n");
        exit(1);
    }
}

foreach (['type_counts', 'structure_counts', 'status_counts', 'quality_counts', 'recent', 'duplicates'] as $metric) {
    if (!str_contains($dashboardModel, "'{$metric}'")) {
        fwrite(STDERR, "Dashboard model is missing {$metric}.\n");
        exit(1);
    }
}

foreach ([
    'xdecaro-dashboard-summary',
    'xdecaro-dashboard-distribution',
    'xdecaro-dashboard-quality',
    'xdecaro-dashboard-recent',
    'filter_search',
] as $needle) {
    if (!str_contains($dashboardLayout, $needle)) {
        fwrite(STDERR, "Dashboard layout is missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    "UPDATE_SITE_URL",
    "pkg_organizations",
    "getTableHealth",
    "getExtension",
    "getIntegrations",
    "minimum_joomla",
    "minimum_php",
    "update_site_enabled",
    "installation_consistent",
] as $needle) {
    if (!str_contains($informationModel, $needle)) {
        fwrite(STDERR, "Information model is missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'xdecaro-information-summary',
    'xdecaro-information-grid',
    'xdecaro-information-diagnostics',
    'data-copy-diagnostics',
    'com_installer&view=update',
] as $needle) {
    if (!str_contains($informationLayout, $needle)) {
        fwrite(STDERR, "Information layout is missing {$needle}.\n");
        exit(1);
    }
}

foreach (['.xdecaro-dashboard-grid', '.xdecaro-dashboard-stat', '.xdecaro-information-grid', '.xdecaro-status-badge'] as $selector) {
    if (!str_contains($css, $selector)) {
        fwrite(STDERR, "Admin CSS is missing {$selector}.\n");
        exit(1);
    }
}

foreach ([
    'COM_XDECAROORGANIZATIONS_DASHBOARD_DATA_QUALITY',
    'COM_XDECAROORGANIZATIONS_DASHBOARD_RECENT',
    'COM_XDECAROORGANIZATIONS_INFO_SYSTEM_STATUS',
    'COM_XDECAROORGANIZATIONS_INFO_COPY_DIAGNOSTICS',
    'COM_XDECAROORGANIZATIONS_INFO_UPDATE_SERVER',
] as $key) {
    if (!str_contains($it, $key . '=') || !str_contains($en, $key . '=')) {
        fwrite(STDERR, "Dashboard/information language key missing: {$key}.\n");
        exit(1);
    }
}

echo "Organizations dashboard and information 1.2.15 contract OK\n";
