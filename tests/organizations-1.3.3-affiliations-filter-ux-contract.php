<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
if ($version === '' || version_compare($version, '1.3.3', '<')) {
    fwrite(STDERR, "VERSION must be 1.3.3 or later\n");
    exit(1);
}
foreach ([$root . '/component/xdecaroorganizations.xml', $root . '/package/pkg_organizations.xml'] as $manifest) {
    $xml = simplexml_load_file($manifest);
    if (!$xml || (string) $xml->version !== $version) {
        fwrite(STDERR, basename($manifest) . " version mismatch\n");
        exit(1);
    }
}
if (!is_file($root . '/component/admin/sql/updates/mysql/1.3.3.sql')) {
    fwrite(STDERR, "Missing 1.3.3 SQL marker\n");
    exit(1);
}
$assets = json_decode((string) file_get_contents($root . '/component/media/joomla.asset.json'), true, 512, JSON_THROW_ON_ERROR);
if (($assets['version'] ?? '') !== $version) {
    fwrite(STDERR, "Asset version mismatch\n");
    exit(1);
}

$modelPath = $root . '/component/admin/src/Model/AffiliationsModel.php';
$templatePath = $root . '/component/admin/tmpl/affiliations/default.php';
$viewPath = $root . '/component/admin/src/View/Affiliations/HtmlView.php';

foreach ([$modelPath, $templatePath, $viewPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, 'Missing required file: ' . $path . "\n");
        exit(1);
    }
}

$model = (string) file_get_contents($modelPath);
foreach ([
    'function getSourceOrganizationOptions()',
    'function getTargetOrganizationOptions()',
    "a.organization_id",
    "a.target_organization_id",
] as $required) {
    if (!str_contains($model, $required)) {
        fwrite(STDERR, "AffiliationsModel must expose relation-scoped source/target options: {$required}\n");
        exit(1);
    }
}

$template = (string) file_get_contents($templatePath);
foreach ([
    '<joomla-field-fancy-select',
    'data-xdecaro-auto-submit="true"',
    '$this->sourceOptions',
    '$this->targetOptions',
    'COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH_ORGANIZATION',
    'COM_XDECAROORGANIZATIONS_GLOBAL_AFFILIATIONS_TITLE',
] as $required) {
    if (!str_contains($template, $required)) {
        fwrite(STDERR, "Affiliations filter UX marker missing: {$required}\n");
        exit(1);
    }
}

$view = (string) file_get_contents($viewPath);
foreach ([
    'public array $sourceOptions = [];',
    'public array $targetOptions = [];',
    "get('SourceOrganizationOptions')",
    "get('TargetOrganizationOptions')",
    "usePreset('choicesjs')",
    "useScript('webcomponent.field-fancy-select')",
    "useScript('com_xdecaroorganizations.global-lists-behavior')",
] as $required) {
    if (!str_contains($view, $required)) {
        fwrite(STDERR, "Affiliations view must load relation-scoped searchable/auto-submit controls: {$required}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Organizations 1.3.3 affiliations filter UX contract: OK\n");
