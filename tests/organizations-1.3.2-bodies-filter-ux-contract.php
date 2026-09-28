<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$modelPath = $root . '/component/admin/src/Model/BodiesModel.php';
$templatePath = $root . '/component/admin/tmpl/bodies/default.php';
$viewPath = $root . '/component/admin/src/View/Bodies/HtmlView.php';
$scriptPath = $root . '/component/media/js/global-lists.js';
$assetPath = $root . '/component/media/joomla.asset.json';

foreach ([$modelPath, $templatePath, $viewPath, $assetPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, 'Missing required file: ' . $path . "\n");
        exit(1);
    }
}

$model = (string) file_get_contents($modelPath);
foreach ([
    "getState('filter.organization')",
    "quoteName('b.organization_id') . ' = :organizationId'",
    "bind(':organizationId'",
    'ParameterType::INTEGER',
] as $required) {
    if (!str_contains($model, $required)) {
        fwrite(STDERR, "BodiesModel must keep exact server-side organization filtering: {$required}\n");
        exit(1);
    }
}

$template = (string) file_get_contents($templatePath);
foreach ([
    '<joomla-field-fancy-select',
    'search-placeholder=',
    'data-xdecaro-auto-submit="true"',
] as $required) {
    if (!str_contains($template, $required)) {
        fwrite(STDERR, "Bodies organization filter UX marker missing: {$required}\n");
        exit(1);
    }
}

$view = (string) file_get_contents($viewPath);
foreach ([
    "usePreset('choicesjs')",
    "useScript('webcomponent.field-fancy-select')",
    "useScript('com_xdecaroorganizations.global-lists')",
] as $required) {
    if (!str_contains($view, $required)) {
        fwrite(STDERR, "Bodies view must load searchable/auto-submit assets: {$required}\n");
        exit(1);
    }
}

$assets = (string) file_get_contents($assetPath);
if (!str_contains($assets, 'com_xdecaroorganizations.global-lists') || !str_contains($assets, 'global-lists.js')) {
    fwrite(STDERR, "Global list script asset is not registered\n");
    exit(1);
}

if (!is_file($scriptPath)) {
    fwrite(STDERR, "Missing global-lists.js\n");
    exit(1);
}

$script = (string) file_get_contents($scriptPath);
foreach ([
    '[data-xdecaro-auto-submit="true"]',
    'requestSubmit',
    "addEventListener('change'",
] as $required) {
    if (!str_contains($script, $required)) {
        fwrite(STDERR, "Auto-submit implementation marker missing: {$required}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Organizations 1.3.2 bodies filter UX contract: OK\n");
