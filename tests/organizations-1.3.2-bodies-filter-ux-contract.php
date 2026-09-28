<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
if ($version !== '1.3.2') { fwrite(STDERR, "VERSION must be 1.3.2\n"); exit(1); }
foreach ([$root . '/component/xdecaroorganizations.xml', $root . '/package/pkg_organizations.xml'] as $manifest) {
    $xml = simplexml_load_file($manifest);
    if (!$xml || (string) $xml->version !== '1.3.2') { fwrite(STDERR, basename($manifest) . " version mismatch\n"); exit(1); }
}
if (!is_file($root . '/component/admin/sql/updates/mysql/1.3.2.sql')) { fwrite(STDERR, "Missing 1.3.2 SQL marker\n"); exit(1); }

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
    'COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH_ORGANIZATION',
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

$assets = json_decode((string) file_get_contents($assetPath), true, 512, JSON_THROW_ON_ERROR);
if (($assets['version'] ?? '') !== '1.3.2') { fwrite(STDERR, "Asset version mismatch\n"); exit(1); }
$assetSource = (string) file_get_contents($assetPath);
if (!str_contains($assetSource, 'com_xdecaroorganizations.global-lists') || !str_contains($assetSource, 'global-lists.js')) {
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

foreach (['it-IT', 'en-GB'] as $locale) {
    $lang = (string) file_get_contents($root . "/component/admin/language/{$locale}/com_xdecaroorganizations.global.ini");
    if (!str_contains($lang, 'COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH_ORGANIZATION=')) {
        fwrite(STDERR, "{$locale}: missing organization search placeholder\n");
        exit(1);
    }
}

fwrite(STDOUT, "Organizations 1.3.2 bodies filter UX contract: OK\n");
