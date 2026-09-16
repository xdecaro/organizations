<?php

$root = dirname(__DIR__);
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');

if (!str_contains($template, "getCmd('activeTab', 'identity')")
    || !str_contains($template, "['identity', 'contacts', 'members', 'publishing', 'system']")
    || !str_contains($template, "['active' => \$activeTab]")) {
    fwrite(STDERR, "Organization edit view must select the active tab from a validated activeTab request value.\n");
    exit(1);
}

foreach ([
    'const reloadMembersTab',
    "searchParams.set('activeTab', 'members')",
    'window.location.assign(url.toString())',
] as $needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "Members actions must reload the organization edit page on the Members tab: {$needle}.\n");
        exit(1);
    }
}

if (substr_count($js, 'reloadMembersTab();') < 3) {
    fwrite(STDERR, "Save, end and delete appointment actions must all return to the Members tab.\n");
    exit(1);
}

if (str_contains($js, 'window.location.reload()')) {
    fwrite(STDERR, "Members actions must not use a plain reload because it resets the tab to Identity.\n");
    exit(1);
}

echo "members tab persistence contract OK\n";
