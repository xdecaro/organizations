<?php

$root = dirname(__DIR__);
$form = (string) file_get_contents($root . '/component/admin/forms/organization.xml');
$field = (string) file_get_contents($root . '/component/admin/src/Field/OrganizationParentField.php');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationModel.php');
$view = (string) file_get_contents($root . '/component/admin/src/View/Organization/HtmlView.php');
$edit = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$templatePath = $root . '/component/admin/tmpl/organization/edit_hierarchy.php';
$template = is_file($templatePath) ? (string) file_get_contents($templatePath) : '';
$it = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$en = (string) file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini');

foreach ([
    'description="COM_XDECAROORGANIZATIONS_FIELD_PARENT_DESC"',
    'label="COM_XDECAROORGANIZATIONS_FIELD_PARENT"',
] as $needle) {
    if (!str_contains($form, $needle)) {
        fwrite(STDERR, "Parent organization field must clearly describe organizational hierarchy: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    "quoteName('structure_level')",
    "STRUCTURE_",
] as $needle) {
    if (!str_contains($field, $needle)) {
        fwrite(STDERR, "Parent selector must show the candidate organization level: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'getHierarchyContext',
    'OrganizationHierarchy::path',
    'OrganizationHierarchy::descendants',
] as $needle) {
    if (!str_contains($model, $needle)) {
        fwrite(STDERR, "Organization model must expose hierarchy context: {$needle}.\n");
        exit(1);
    }
}

foreach (['hierarchyPath', 'hierarchyDescendants', 'loadHierarchyContext'] as $needle) {
    if (!str_contains($view, $needle)) {
        fwrite(STDERR, "Organization view must load hierarchy context: {$needle}.\n");
        exit(1);
    }
}

if (!str_contains($edit, "'hierarchy'") || !str_contains($edit, "loadTemplate('hierarchy')")) {
    fwrite(STDERR, "Organization editor must include the Hierarchy tab.\n");
    exit(1);
}

foreach ([
    'COM_XDECAROORGANIZATIONS_HIERARCHY_PATH',
    'COM_XDECAROORGANIZATIONS_HIERARCHY_CHILDREN',
    'hierarchy_depth',
    'task=organization.edit',
] as $needle) {
    if (!str_contains($template, $needle)) {
        fwrite(STDERR, "Hierarchy tab missing {$needle}.\n");
        exit(1);
    }
}

foreach ([$it, $en] as $language) {
    foreach ([
        'COM_XDECAROORGANIZATIONS_FIELDSET_HIERARCHY=',
        'COM_XDECAROORGANIZATIONS_FIELD_PARENT_DESC=',
        'COM_XDECAROORGANIZATIONS_HIERARCHY_ORGANIZATIONS_ONLY=',
    ] as $needle) {
        if (!str_contains($language, $needle)) {
            fwrite(STDERR, "Hierarchy translations missing {$needle}.\n");
            exit(1);
        }
    }
}

echo "Organizations hierarchy context contract OK\n";
