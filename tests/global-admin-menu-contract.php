<?php

declare(strict_types=1);

$manifestPath = __DIR__ . '/../component/xdecaroorganizations.xml';
if (!is_file($manifestPath)) {
    fwrite(STDERR, "Missing component manifest\n");
    exit(1);
}

$xml = simplexml_load_file($manifestPath);
if ($xml === false) {
    fwrite(STDERR, "Invalid component manifest XML\n");
    exit(1);
}

$views = [];
foreach ($xml->administration->submenu->menu as $menu) {
    $views[] = (string) $menu['view'];
}

$expected = [
    'dashboard',
    'organizations',
    'affiliations',
    'appointments',
    'delegations',
    'hierarchy',
    'bodies',
    'duplicates',
    'maintenance',
    'information',
];

if ($views !== $expected) {
    fwrite(STDERR, 'Unexpected submenu order: ' . json_encode($views) . "\n");
    exit(1);
}

$sysFiles = [
    __DIR__ . '/../component/admin/language/en-GB/com_xdecaroorganizations.sys.ini',
    __DIR__ . '/../component/admin/language/it-IT/com_xdecaroorganizations.sys.ini',
];
$keys = [
    'COM_XDECAROORGANIZATIONS_AFFILIATIONS',
    'COM_XDECAROORGANIZATIONS_APPOINTMENTS',
    'COM_XDECAROORGANIZATIONS_DELEGATIONS',
    'COM_XDECAROORGANIZATIONS_HIERARCHY',
    'COM_XDECAROORGANIZATIONS_BODIES',
];

foreach ($sysFiles as $file) {
    $content = (string) file_get_contents($file);
    foreach ($keys as $key) {
        if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
            fwrite(STDERR, basename($file) . " missing $key\n");
            exit(1);
        }
    }
}

fwrite(STDOUT, "global admin menu contract: OK\n");
