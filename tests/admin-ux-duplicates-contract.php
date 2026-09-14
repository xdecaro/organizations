<?php
$root = dirname(__DIR__);
$duplicates = file_get_contents($root . '/component/admin/src/Service/DuplicateService.php');
$layout = file_get_contents($root . '/component/admin/tmpl/organizations/default.php');
$it = file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$en = file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini');

foreach (['name', 'legal_name', 'code', 'email', 'pec_email'] as $field) {
    if (!str_contains($duplicates, "'{$field}'")) {
        fwrite(STDERR, "Duplicate detection must include {$field}.\n");
        exit(1);
    }
}

foreach (['N_ITEMS_PUBLISHED', 'N_ITEMS_UNPUBLISHED', 'N_ITEMS_TRASHED', 'N_ITEMS_DELETED'] as $suffix) {
    $key = 'COM_XDECAROORGANIZATIONS_' . $suffix;
    if (!str_contains($it, $key . '=')) {
        fwrite(STDERR, "Italian language is missing {$key}.\n");
        exit(1);
    }
    if (!str_contains($en, $key . '=')) {
        fwrite(STDERR, "English language is missing {$key}.\n");
        exit(1);
    }
}

if (!str_contains($layout, 'xdecaro-search-row') || !str_contains($layout, 'col-12')) {
    fwrite(STDERR, "Organizations search must use a full-width search row.\n");
    exit(1);
}

echo "Organizations admin UX and duplicates contract OK\n";
