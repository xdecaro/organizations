<?php

declare(strict_types=1);

$view = __DIR__ . '/../component/admin/src/View/Affiliations/HtmlView.php';
$template = __DIR__ . '/../component/admin/tmpl/affiliations/default.php';

foreach ([$view, $template] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, 'Missing ' . basename(dirname($path)) . '/' . basename($path) . "\n");
        exit(1);
    }
}

$viewSource = (string) file_get_contents($view);
$templateSource = (string) file_get_contents($template);

foreach ([
    "core.manage",
    "com_xdecaroorganizations.global-lists",
    "OrganizationOptions",
] as $needle) {
    if (!str_contains($viewSource, $needle)) {
        fwrite(STDERR, "Affiliations HtmlView missing: $needle\n");
        exit(1);
    }
}

foreach ([
    'xdecaro-global-list-table',
    'xdecaro-global-list-cards',
    'filter_perspective',
    'filter_source',
    'filter_target',
    'filter_relation_type',
    'filter_status',
    'filter_temporal',
    'tab=affiliations',
    'xdecaro-global-list-open',
] as $needle) {
    if (!str_contains($templateSource, $needle)) {
        fwrite(STDERR, "Affiliations template missing: $needle\n");
        exit(1);
    }
}

foreach (['data-add', 'data-edit', 'data-delete', 'task=save', 'task=delete'] as $forbidden) {
    if (stripos($templateSource, $forbidden) !== false) {
        fwrite(STDERR, "Read-only Affiliations template contains mutation marker: $forbidden\n");
        exit(1);
    }
}

fwrite(STDOUT, "global affiliations UI contract: OK\n");
