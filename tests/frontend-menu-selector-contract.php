<?php

$root = dirname(__DIR__);

$field = (string) file_get_contents($root . '/component/site/src/Field/OrganizationMenuField.php');
$menu = (string) file_get_contents($root . '/component/site/tmpl/organization/default.xml');
$listModel = (string) file_get_contents($root . '/component/site/src/Model/OrganizationsModel.php');
$itemModel = (string) file_get_contents($root . '/component/site/src/Model/OrganizationModel.php');
$css = (string) file_get_contents($root . '/component/media/css/site.css');

foreach ([
    "protected \$type = 'OrganizationMenu'",
    'operational_status',
    'COM_XDECAROORGANIZATIONS_OPERATIONAL_',
    'COM_XDECAROORGANIZATIONS_SITE_MENU_NOT_PUBLISHED',
    '$disabled = !$published || !$active',
] as $needle) {
    if (!str_contains($field, $needle)) {
        fwrite(STDERR, "Organization menu selector missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'type="OrganizationMenu"',
    'name="organization_id"',
    'addfieldprefix="xdecaro\\Component\\Organizations\\Site\\Field"',
] as $needle) {
    if (!str_contains($menu, $needle)) {
        fwrite(STDERR, "Organization menu metadata missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    "getInt('organization_id', 0)",
    "getInt('id', 0)",
    "get('organization_id', 0)",
] as $needle) {
    if (!str_contains($itemModel, $needle)) {
        fwrite(STDERR, "Organization ID resolution contract missing {$needle}.\n");
        exit(1);
    }
}

if (!str_contains($listModel, "operational_status") || !str_contains($listModel, "quote('active')")) {
    fwrite(STDERR, "Public organization directory must require operational_status=active.\n");
    exit(1);
}

if (substr_count($itemModel, "operational_status") < 3 || substr_count($itemModel, "quote('active')") < 3) {
    fwrite(STDERR, "Public organization profile/hierarchy must require active structures.\n");
    exit(1);
}

foreach ([
    '.xo-badges .badge',
    '.xo-badges .text-bg-light',
    'var(--body-color, inherit)',
] as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "Public badge contrast contract missing {$needle}.\n");
        exit(1);
    }
}

echo "Organizations frontend menu selector contract OK\n";
