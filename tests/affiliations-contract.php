<?php
$root = dirname(__DIR__);
$install = file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$update = file_get_contents($root . '/component/admin/sql/updates/mysql/1.2.5.sql');
$edit = file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$template = file_get_contents($root . '/component/admin/tmpl/organization/edit_affiliations.php');
$js = file_get_contents($root . '/component/media/js/organization-edit.js');
$provider = file_get_contents($root . '/component/admin/src/Service/OrganizationProviderService.php');

$checks = [
    'install table' => str_contains($install, '#__xdecaroorganizations_affiliations'),
    'update table' => str_contains($update, '#__xdecaroorganizations_affiliations'),
    'tab' => str_contains($edit, "'affiliations'"),
    'template target' => str_contains($template, 'target_organization_id') || str_contains($template, 'affiliation-target'),
    'ajax save' => str_contains($js, 'affiliation.save'),
    'ajax delete' => str_contains($js, 'affiliation.delete'),
    'provider api' => str_contains($provider, 'getAffiliations'),
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

echo "OK affiliations contract\n";
