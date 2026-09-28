<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$templates = [
    'affiliations' => [
        'filter_perspective',
        'filter_source',
        'filter_target',
        'filter_relation_type',
        'filter_status',
        'filter_temporal',
        'limit',
    ],
    'appointments' => [
        'filter_organization',
        'filter_body',
        'filter_role',
        'filter_visual_status',
        'limit',
    ],
    'delegations' => [
        'filter_organization',
        'filter_visual_status',
        'filter_temporal',
        'limit',
    ],
    'hierarchy' => [
        'filter_type',
        'filter_structure',
        'filter_operational',
        'filter_roots_only',
        'limit',
    ],
    'bodies' => [
        'filter_organization',
        'filter_body_type',
        'filter_visual_status',
        'limit',
    ],
];

$selectTag = static function (string $template, string $id): string {
    if (!preg_match('/<select\\b[^>]*\\bid="' . preg_quote($id, '/') . '"[^>]*>/i', $template, $match)) {
        fwrite(STDERR, "Missing select #{$id}\n");
        exit(1);
    }

    return $match[0];
};

$searchTag = static function (string $template): string {
    if (!preg_match('/<input\\b[^>]*\\bid="filter_search"[^>]*>/i', $template, $match)) {
        fwrite(STDERR, "Missing search input #filter_search\n");
        exit(1);
    }

    return $match[0];
};

foreach ($templates as $view => $selectIds) {
    $path = $root . '/component/admin/tmpl/' . $view . '/default.php';

    if (!is_file($path)) {
        fwrite(STDERR, "Missing global view template: {$view}\n");
        exit(1);
    }

    $template = (string) file_get_contents($path);

    foreach ($selectIds as $id) {
        $tag = $selectTag($template, $id);

        if (!str_contains($tag, 'data-xdecaro-auto-submit="true"')) {
            fwrite(STDERR, "{$view} #{$id} must auto-submit on change\n");
            exit(1);
        }
    }

    $search = $searchTag($template);

    if (!str_contains($search, 'data-xdecaro-live-search="true"')) {
        fwrite(STDERR, "{$view} #filter_search must use debounced live filtering\n");
        exit(1);
    }

    if (!str_contains($template, 'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER')) {
        fwrite(STDERR, "{$view} must preserve the explicit Filter button as fallback\n");
        exit(1);
    }

    $viewPath = $root . '/component/admin/src/View/' . ucfirst($view) . '/HtmlView.php';
    if (!is_file($viewPath)) {
        fwrite(STDERR, "Missing HtmlView for {$view}\n");
        exit(1);
    }

    $viewSource = (string) file_get_contents($viewPath);
    if (!str_contains($viewSource, "useScript('com_xdecaroorganizations.global-lists-behavior')")) {
        fwrite(STDERR, "{$view} must load the shared global filter behavior\n");
        exit(1);
    }
}

$scriptPath = $root . '/component/media/js/global-lists.js';
if (!is_file($scriptPath)) {
    fwrite(STDERR, "Missing shared global list behavior\n");
    exit(1);
}

$script = (string) file_get_contents($scriptPath);
foreach ([
    '[data-xdecaro-auto-submit="true"]',
    '[data-xdecaro-live-search="true"]',
    'addEventListener(\'change\'',
    'addEventListener(\'input\'',
    'setTimeout',
    'requestSubmit',
    'xdecaroAutoSubmitBound',
    'xdecaroLiveSearchBound',
] as $required) {
    if (!str_contains($script, $required)) {
        fwrite(STDERR, "Shared global filter behavior missing marker: {$required}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Organizations 1.3.4 global filter auto-submit contract: OK\n");
