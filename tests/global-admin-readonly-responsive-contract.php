<?php

declare(strict_types=1);

$cssPath = __DIR__ . '/../component/media/css/global-lists.css';
$assetPath = __DIR__ . '/../component/media/joomla.asset.json';

if (!is_file($cssPath)) {
    fwrite(STDERR, "Missing global-lists.css\n");
    exit(1);
}

$css = (string) file_get_contents($cssPath);
$requiredCss = [
    '.xdecaro-global-list-table',
    '.xdecaro-global-list-cards',
    '@media (max-width: 767.98px)',
    '.xdecaro-global-list-open',
];
foreach ($requiredCss as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "global-lists.css missing $needle\n");
        exit(1);
    }
}

foreach (['data-edit', 'data-delete', 'data-add', 'task=delete', 'task=save'] as $forbidden) {
    if (stripos($css, $forbidden) !== false) {
        fwrite(STDERR, "Read-only CSS contains mutation marker $forbidden\n");
        exit(1);
    }
}

$assets = json_decode((string) file_get_contents($assetPath), true, 512, JSON_THROW_ON_ERROR);
$found = false;
foreach (($assets['assets'] ?? []) as $asset) {
    if (($asset['name'] ?? '') === 'com_xdecaroorganizations.global-lists'
        && ($asset['type'] ?? '') === 'style'
        && ($asset['uri'] ?? '') === 'com_xdecaroorganizations/global-lists.css') {
        $found = true;
        break;
    }
}

if (!$found) {
    fwrite(STDERR, "Global list stylesheet asset is not registered\n");
    exit(1);
}

fwrite(STDOUT, "global admin read-only responsive contract: OK\n");
