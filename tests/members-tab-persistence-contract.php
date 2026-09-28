<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');

if (!str_contains($template, "getCmd('activeTab', 'identity')")
    || !str_contains($template, "'members'")
    || !str_contains($template, '$activeTab === $sectionId')
    || !str_contains($template, 'id="organization-active-tab"')
    || !str_contains($template, 'data-organization-section=')) {
    fwrite(STDERR, "Organization edit view must select and preserve the active accordion section from a validated activeTab request value.\n");
    exit(1);
}

foreach ([
    'const reloadMembersTab',
    "const reloadMembersTab = () => reloadOrganizationTab('members')",
    "url.searchParams.set('activeTab', tab)",
    'window.location.assign(url.toString())',
] as $needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "Members actions must reload the organization edit page on the Members accordion section: {$needle}.\n");
        exit(1);
    }
}

if (substr_count($js, 'reloadMembersTab();') < 3) {
    fwrite(STDERR, "Save, end and delete appointment actions must all return to the Members accordion section.\n");
    exit(1);
}

if (str_contains($js, 'window.location.reload()')) {
    fwrite(STDERR, "Members actions must not use a plain reload because it resets the active section to Identity.\n");
    exit(1);
}

echo "members accordion persistence contract OK\n";
