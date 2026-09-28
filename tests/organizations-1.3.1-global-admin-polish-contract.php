<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$templates = [
    'affiliations' => $root . '/component/admin/tmpl/affiliations/default.php',
    'appointments' => $root . '/component/admin/tmpl/appointments/default.php',
    'delegations' => $root . '/component/admin/tmpl/delegations/default.php',
    'hierarchy' => $root . '/component/admin/tmpl/hierarchy/default.php',
    'bodies' => $root . '/component/admin/tmpl/bodies/default.php',
];

foreach ($templates as $name => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing global admin template: {$name}\n");
        exit(1);
    }

    $source = (string) file_get_contents($path);

    foreach (['JOPTION_SELECT_ALL', "Text::_('JFILTER')", "Text::_('JACTIONS')"] as $forbidden) {
        if (str_contains($source, $forbidden)) {
            fwrite(STDERR, "{$name}: raw Joomla UI key still used: {$forbidden}\n");
            exit(1);
        }
    }

    foreach ([
        'COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL',
        'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER',
        'COM_XDECAROORGANIZATIONS_GLOBAL_ACTIONS',
    ] as $required) {
        if (!str_contains($source, $required)) {
            fwrite(STDERR, "{$name}: missing localized UI key {$required}\n");
            exit(1);
        }
    }
}

foreach (['affiliations', 'appointments', 'delegations', 'bodies'] as $name) {
    $source = (string) file_get_contents($templates[$name]);
    foreach (['formatDate', 'd/m/Y'] as $required) {
        if (!str_contains($source, $required)) {
            fwrite(STDERR, "{$name}: missing dd/mm/yyyy date formatter marker {$required}\n");
            exit(1);
        }
    }
}

$hierarchy = (string) file_get_contents($templates['hierarchy']);
foreach ([
    'COM_XDECAROORGANIZATIONS_TYPE_',
    'COM_XDECAROORGANIZATIONS_STRUCTURE_',
    'COM_XDECAROORGANIZATIONS_OPERATIONAL_',
    'badge',
] as $required) {
    if (!str_contains($hierarchy, $required)) {
        fwrite(STDERR, "hierarchy: missing localized label/badge marker {$required}\n");
        exit(1);
    }
}

$bodies = (string) file_get_contents($templates['bodies']);
foreach (['COM_XDECAROORGANIZATIONS_BODY_STATUS_', 'badge'] as $required) {
    if (!str_contains($bodies, $required)) {
        fwrite(STDERR, "bodies: missing localized status marker {$required}\n");
        exit(1);
    }
}

$delegations = (string) file_get_contents($templates['delegations']);
if (!str_contains($delegations, 'statusLabel') || !str_contains($delegations, 'badge')) {
    fwrite(STDERR, "delegations: localized status badge missing\n");
    exit(1);
}

$appointments = (string) file_get_contents($templates['appointments']);
if (!str_contains($appointments, 'statusBadgeClass') || !str_contains($appointments, 'badge')) {
    fwrite(STDERR, "appointments: localized status badge missing\n");
    exit(1);
}

$affiliations = (string) file_get_contents($templates['affiliations']);
if (!str_contains($affiliations, 'statusBadgeClass') || !str_contains($affiliations, 'badge')) {
    fwrite(STDERR, "affiliations: localized status badge missing\n");
    exit(1);
}

foreach (['it-IT', 'en-GB'] as $locale) {
    $languagePath = $root . "/component/admin/language/{$locale}/com_xdecaroorganizations.global.ini";
    $language = is_file($languagePath) ? (string) file_get_contents($languagePath) : '';
    foreach ([
        'COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL=',
        'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER=',
        'COM_XDECAROORGANIZATIONS_GLOBAL_ACTIONS=',
        'COM_XDECAROORGANIZATIONS_GLOBAL_STATUS=',
    ] as $required) {
        if (!str_contains($language, $required)) {
            fwrite(STDERR, "{$locale}: missing language key {$required}\n");
            exit(1);
        }
    }
}

fwrite(STDOUT, "Organizations 1.3.1 global admin polish contract: OK\n");
