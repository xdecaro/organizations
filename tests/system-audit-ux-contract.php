<?php

$root = dirname(__DIR__);
$view = (string) file_get_contents($root . '/component/admin/src/View/Organization/HtmlView.php');
$edit = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$systemPath = $root . '/component/admin/tmpl/organization/edit_system.php';
$system = is_file($systemPath) ? (string) file_get_contents($systemPath) : '';
$form = (string) file_get_contents($root . '/component/admin/forms/organization.xml');
$it = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$en = (string) file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini');

foreach ([
    'UserFactoryInterface',
    'loadSystemAudit',
    'auditCreatedByName',
    'auditModifiedByName',
] as $needle) {
    if (!str_contains($view, $needle)) {
        fwrite(STDERR, "System audit view missing {$needle}.\n");
        exit(1);
    }
}

if (str_contains($edit, "renderFieldset('system')") || !str_contains($edit, "loadTemplate('system')")) {
    fwrite(STDERR, "System tab must use the dedicated read-only template.\n");
    exit(1);
}

foreach ([
    'DATE_FORMAT_LC2',
    'COM_XDECAROORGANIZATIONS_FIELD_CREATED_BY',
    'COM_XDECAROORGANIZATIONS_FIELD_MODIFIED_BY',
    'COM_XDECAROORGANIZATIONS_FIELD_MODIFIED_ORGANIZATION',
    'COM_XDECAROORGANIZATIONS_SYSTEM_AUDIT_DESC',
] as $needle) {
    if (!str_contains($system, $needle)) {
        fwrite(STDERR, "System audit template missing {$needle}.\n");
        exit(1);
    }
}

if (!str_contains($form, 'label="COM_XDECAROORGANIZATIONS_FIELD_PUBLISHING_NOTES"')) {
    fwrite(STDERR, "Publishing notes must have an unambiguous publishing-specific label.\n");
    exit(1);
}

foreach ([$it, $en] as $language) {
    foreach ([
        'COM_XDECAROORGANIZATIONS_FIELD_CREATED_BY=',
        'COM_XDECAROORGANIZATIONS_FIELD_MODIFIED_BY=',
        'COM_XDECAROORGANIZATIONS_FIELD_MODIFIED_ORGANIZATION=',
        'COM_XDECAROORGANIZATIONS_FIELD_PUBLISHING_NOTES=',
        'COM_XDECAROORGANIZATIONS_SYSTEM_AUDIT_DESC=',
    ] as $needle) {
        if (!str_contains($language, $needle)) {
            fwrite(STDERR, "System/publishing translations missing {$needle}.\n");
            exit(1);
        }
    }
}

echo "Organizations system audit UX contract OK\n";
