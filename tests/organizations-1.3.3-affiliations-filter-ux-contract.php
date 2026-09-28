<?php

declare(strict_types=1);

$root = dirname(__DIR__);
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
