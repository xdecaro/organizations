<?php
$root = dirname(__DIR__);
$install = file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$update = file_get_contents($root . '/component/admin/sql/updates/mysql/1.2.5.sql');
$edit = file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$template = file_get_contents($root . '/component/admin/tmpl/organization/edit_affiliations.php');
$js = file_get_contents($root . '/component/media/js/organization-edit.js');
$controller = file_get_contents($root . '/component/admin/src/Controller/AffiliationController.php');
$model = file_get_contents($root . '/component/admin/src/Model/OrganizationModel.php');
$list = file_get_contents($root . '/component/admin/tmpl/organizations/default.php');
$it = file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$provider = file_get_contents($root . '/component/admin/src/Service/OrganizationProviderService.php');

$checks = [
    'install table' => str_contains($install, '#__xdecaroorganizations_affiliations'),
    'update table' => str_contains($update, '#__xdecaroorganizations_affiliations'),
    'tab' => str_contains($edit, "'affiliations'"),
    'template target' => str_contains($template, 'affiliation-target'),
    'template search input' => str_contains($template, 'data-affiliation-target-search'),
    'template no full preload' => !str_contains($template, 'foreach ($this->affiliationTargets'),
    'ajax search' => str_contains($js, 'affiliation.searchTargets'),
    'ajax save' => str_contains($js, 'affiliation.save'),
    'ajax delete' => str_contains($js, 'affiliation.delete'),
    'controller search endpoint' => str_contains($controller, 'function searchTargets'),
    'model name search' => str_contains($model, "quoteName('name') . ' LIKE :nameSearch"),
    'model acronym search' => str_contains($model, "quoteName('code') . ' LIKE :codeSearch"),
    'bounded results' => str_contains($model, 'setQuery($query, 0, $limit)'),
    'organization list acronym column' => str_contains($list, 'COM_XDECAROORGANIZATIONS_COLUMN_ACRONYM') && str_contains($list, '$item->code'),
    'italian acronym label' => str_contains($it, 'COM_XDECAROORGANIZATIONS_COLUMN_ACRONYM="Sigla"'),
    'provider api' => str_contains($provider, 'getAffiliations'),
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

echo "OK affiliations contract\n";
