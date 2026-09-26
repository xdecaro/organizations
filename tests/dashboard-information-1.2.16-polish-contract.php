<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$files = [
    'model' => $root . '/component/admin/src/Model/InformationModel.php',
    'dashboard' => $root . '/component/admin/tmpl/dashboard/default.php',
    'information' => $root . '/component/admin/tmpl/information/default.php',
    'css' => $root . '/component/media/css/admin.css',
    'it' => $root . '/component/admin/language/it-IT/com_xdecaroorganizations.dashboard.ini',
    'en' => $root . '/component/admin/language/en-GB/com_xdecaroorganizations.dashboard.ini',
];

foreach ($files as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing {$name} file: {$path}\n");
        exit(1);
    }
}

$model = (string) file_get_contents($files['model']);
$dashboard = (string) file_get_contents($files['dashboard']);
$information = (string) file_get_contents($files['information']);
$css = (string) file_get_contents($files['css']);
$it = (string) file_get_contents($files['it']);
$en = (string) file_get_contents($files['en']);

foreach ([
    "'update_checked'",
    "'effective_latest_version'",
] as $needle) {
    if (!str_contains($model, $needle)) {
        fwrite(STDERR, "Information update diagnostics missing {$needle}.\n");
        exit(1);
    }
}

if (str_contains($dashboard, 'card h-100 xdecaro-dashboard-quality')) {
    fwrite(STDERR, "Data quality card must not be forced to match the recent-items card height.\n");
    exit(1);
}

if (!str_contains($dashboard, 'card xdecaro-dashboard-quality')) {
    fwrite(STDERR, "Data quality card marker is missing.\n");
    exit(1);
}

foreach ([
    'xdecaro-diagnostic-table',
    'COM_XDECAROORGANIZATIONS_INFO_TABLE_ORGANIZATIONS',
    'COM_XDECAROORGANIZATIONS_INFO_TABLE_BODIES',
    'COM_XDECAROORGANIZATIONS_INFO_TABLE_APPOINTMENTS',
    'COM_XDECAROORGANIZATIONS_INFO_TABLE_DELEGATIONS',
    'COM_XDECAROORGANIZATIONS_INFO_TABLE_AFFILIATIONS',
    'COM_XDECAROORGANIZATIONS_INFO_NO_UPDATE_AVAILABLE',
] as $needle) {
    if (!str_contains($information, $needle) && !str_contains($it, $needle . '=') && !str_contains($en, $needle . '=')) {
        fwrite(STDERR, "Information polish marker missing: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    '.xdecaro-diagnostic-table',
    '.xdecaro-diagnostic-table code',
] as $selector) {
    if (!str_contains($css, $selector)) {
        fwrite(STDERR, "Admin CSS missing {$selector}.\n");
        exit(1);
    }
}

echo "Organizations dashboard/information 1.2.16 polish contract OK\n";
