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

$required = [
    '.xdecaro-activity-details-cell::before',
    'display: none',
    '.xdecaro-activity-details-cell {',
    'justify-items: end',
    '.xdecaro-activity-details {',
    'display: inline-grid',
    '.xdecaro-activity-details[open]',
    'width: 100%',
    'justify-self: end',
];

foreach ($required as $needle) {
    if (!str_contains($mobileCss, $needle)) {
        fwrite(STDERR, "Missing compact mobile details rule: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($mobileCss, '.xdecaro-activity-details-panel') || !str_contains($mobileCss, 'position: static')) {
    fwrite(STDERR, "Expanded mobile details panel must remain in normal flow.\n");
    exit(1);
}

if (!str_contains($css, '.xdecaro-activity-table th:nth-child(6)') || !str_contains($css, 'width: 7.5rem')) {
    fwrite(STDERR, "Desktop activity details column regression detected.\n");
    exit(1);
}

echo "Organizations 1.2.20 compact mobile details contract OK\n";
