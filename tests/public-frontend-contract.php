<?php

$root = dirname(__DIR__);

$manifest = (string) file_get_contents($root . '/component/xdecaroorganizations.xml');
$assets = (string) file_get_contents($root . '/component/media/joomla.asset.json');
$controller = (string) file_get_contents($root . '/component/site/src/Controller/DisplayController.php');
$listModel = (string) file_get_contents($root . '/component/site/src/Model/OrganizationsModel.php');
$itemModel = (string) file_get_contents($root . '/component/site/src/Model/OrganizationModel.php');
$listView = (string) file_get_contents($root . '/component/site/src/View/Organizations/HtmlView.php');
$itemView = (string) file_get_contents($root . '/component/site/src/View/Organization/HtmlView.php');
$listLayout = (string) file_get_contents($root . '/component/site/tmpl/organizations/default.php');
$itemLayout = (string) file_get_contents($root . '/component/site/tmpl/organization/default.php');
$listMenu = (string) file_get_contents($root . '/component/site/tmpl/organizations/default.xml');
$itemMenu = (string) file_get_contents($root . '/component/site/tmpl/organization/default.xml');

foreach ([
    '<files folder="site">',
    '<folder>language</folder>',
    '<folder>src</folder>',
    '<folder>tmpl</folder>',
    '<version>1.2.5</version>',
] as $needle) {
    if (!str_contains($manifest, $needle)) {
        fwrite(STDERR, "Frontend manifest contract missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'com_xdecaroorganizations.site',
    'com_xdecaroorganizations/site.css',
] as $needle) {
    if (!str_contains($assets, $needle)) {
        fwrite(STDERR, "Frontend asset contract missing {$needle}.\n");
        exit(1);
    }
}

if (!str_contains($controller, "protected \$default_view = 'organizations'")) {
    fwrite(STDERR, "Frontend DisplayController must default to organizations.\n");
    exit(1);
}

foreach ([$listModel, $itemModel] as $model) {
    foreach ([
        "state') . ' = 1",
        'getAuthorisedViewLevels',
        "language",
    ] as $needle) {
        if (!str_contains($model, $needle)) {
            fwrite(STDERR, "Public model contract missing {$needle}.\n");
            exit(1);
        }
    }
}

foreach ([
    'show_on_frontend',
    'getAppointments',
    'getDelegations',
    'getHierarchyPath',
    'getChildren',
    'getBodies',
] as $needle) {
    if (!str_contains($itemModel, $needle)) {
        fwrite(STDERR, "Public organization profile model missing {$needle}.\n");
        exit(1);
    }
}

foreach ([$listView, $itemView] as $view) {
    if (!str_contains($view, "useStyle('com_xdecaroorganizations.site')")) {
        fwrite(STDERR, "Public view must load the Organizations site style.\n");
        exit(1);
    }
}

foreach ([
    'view=organization&id=',
    'Itemid',
    'xo-card-grid',
] as $needle) {
    if (!str_contains($listLayout, $needle)) {
        fwrite(STDERR, "Organizations directory layout missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'xo-profile-header',
    'xo-body-grid',
    'xo-delegation-grid',
    'xo-linked-list',
] as $needle) {
    if (!str_contains($itemLayout, $needle)) {
        fwrite(STDERR, "Organization public profile layout missing {$needle}.\n");
        exit(1);
    }
}

if (!str_contains($listMenu, 'COM_XDECAROORGANIZATIONS_VIEW_ORGANIZATIONS_DEFAULT_TITLE')
    || !str_contains($itemMenu, 'COM_XDECAROORGANIZATIONS_VIEW_ORGANIZATION_DEFAULT_TITLE')
    || !str_contains($itemMenu, 'name="organization_id"')) {
    fwrite(STDERR, "Frontend Joomla menu metadata is incomplete.\n");
    exit(1);
}

echo "Organizations public frontend contract OK\n";
