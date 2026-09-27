<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$cssPath = $root . '/component/media/css/maintenance.css';

if (!is_file($cssPath)) {
    fwrite(STDERR, "Missing maintenance CSS: {$cssPath}\n");
    exit(1);
}

$css = (string) file_get_contents($cssPath);
$mobilePos = strpos($css, '@media (max-width: 767.98px)');
if ($mobilePos === false) {
    fwrite(STDERR, "Missing mobile maintenance breakpoint.\n");
    exit(1);
}

$mobileCss = substr($css, $mobilePos);
$normalized = preg_replace('/\s+/', ' ', $mobileCss);
if (!is_string($normalized)) {
    fwrite(STDERR, "Unable to normalize maintenance CSS.\n");
    exit(1);
}

$required = [
    '.xdecaro-maintenance-page .xdecaro-activity-table td.xdecaro-activity-details-cell::before { display: none;',
    '.xdecaro-maintenance-page .xdecaro-activity-table td.xdecaro-activity-details-cell { display: block !important; width: 100%; margin-top: .5rem; padding-top: .5rem;',
    'border-top: 1px solid var(--border-color, rgba(127, 127, 127, .18)); text-align: left !important;',
    '.xdecaro-maintenance-page .xdecaro-activity-details { display: block; width: 100%; margin: 0;',
    '.xdecaro-maintenance-page .xdecaro-activity-details > summary { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 40px;',
    '.xdecaro-maintenance-page .xdecaro-activity-details-panel { position: static; width: 100%; margin-top: .5rem; box-shadow: none;',
];

foreach ($required as $needle) {
    if (!str_contains($normalized, $needle)) {
        fwrite(STDERR, "Missing mobile details card-action rule: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($css, '.xdecaro-maintenance-page .xdecaro-activity-table th:nth-child(6)') || !str_contains($css, 'width: 7.5rem')) {
    fwrite(STDERR, "Desktop activity details column regression detected.\n");
    exit(1);
}

echo "Organizations 1.2.21 mobile details card-action contract OK\n";
