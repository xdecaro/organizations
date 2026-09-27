<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$cssPath = $root . '/component/media/css/maintenance.css';

if (!is_file($cssPath)) {
    fwrite(STDERR, "Missing maintenance CSS: {$cssPath}\n");
    exit(1);
}

$css = (string) file_get_contents($cssPath);
$desktopPos = strpos($css, '@media (min-width: 768px)');
if ($desktopPos === false) {
    fwrite(STDERR, "Missing desktop maintenance breakpoint for Details cleanup.\n");
    exit(1);
}

$desktopCss = substr($css, $desktopPos);
$normalized = preg_replace('/\s+/', ' ', $desktopCss);
if (!is_string($normalized)) {
    fwrite(STDERR, "Unable to normalize maintenance CSS.\n");
    exit(1);
}

$required = [
    '.xdecaro-maintenance-page .xdecaro-activity-details { padding: 0 !important;',
    'border: 0 !important;',
    'background: transparent !important;',
    'box-shadow: none !important;',
];

foreach ($required as $needle) {
    if (!str_contains($normalized, $needle)) {
        fwrite(STDERR, "Missing desktop Details cleanup rule: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($css, '@media (max-width: 767.98px)')
    || !str_contains($css, '.xdecaro-maintenance-page .xdecaro-activity-table td.xdecaro-activity-details-cell::before')
    || !str_contains($css, '.xdecaro-maintenance-page .xdecaro-activity-details > summary')) {
    fwrite(STDERR, "Mobile 1.2.21 Details layout regression detected.\n");
    exit(1);
}

echo "Organizations 1.2.22 desktop Details cleanup contract OK\n";
