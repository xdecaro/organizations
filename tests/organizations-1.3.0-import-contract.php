<?php

$root = dirname(__DIR__);

$controller = (string) file_get_contents($root . '/component/admin/src/Controller/ImportController.php');
$service = (string) file_get_contents($root . '/component/admin/src/Service/ImportService.php');
$view = (string) file_get_contents($root . '/component/admin/src/View/Import/HtmlView.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/import/default.php');
$listView = (string) file_get_contents($root . '/component/admin/src/View/Organizations/HtmlView.php');
$extension = (string) file_get_contents($root . '/component/admin/src/Extension/OrganizationsComponent.php');
$provider = (string) file_get_contents($root . '/component/admin/services/provider.php');
$assets = (string) file_get_contents($root . '/component/media/joomla.asset.json');
$js = (string) file_get_contents($root . '/component/media/js/import.js');

$checks = [
    'toolbar import action' => str_contains($listView, "import.open"),
    'controller csrf' => str_contains($controller, "Session::checkToken('post')"),
    'controller acl create' => str_contains($controller, "core.create"),
    'controller acl sensitive' => str_contains($controller, "organizations.view_sensitive"),
    'server row limit' => str_contains($controller, "count(\$rows) > 5000"),
    'batch limit' => str_contains($controller, "count(\$rows) > 150"),
    'component import service' => str_contains($extension, 'getImportService'),
    'provider import service' => str_contains($provider, 'ImportService::class'),
    'existing rows are not updated' => !str_contains($service, 'updateObject'),
    'parent hierarchy is never imported' => str_contains($service, "'parent_id' => null"),
    'affiliation insert' => str_contains($service, '#__xdecaroorganizations_affiliations'),
    'affiliation missing target blocked' => str_contains($service, 'affiliation_target_not_found'),
    'existing conflicts blocked' => str_contains($service, 'existing_identifier_conflict'),
    'duplicate conflicts blocked' => str_contains($service, 'duplicate_conflict'),
    'country metadata' => str_contains($service, 'CountryMetadata::countries'),
    'import view asset' => str_contains($view, "useScript('com_xdecaroorganizations.import')"),
    'csv only input' => str_contains($template, 'accept=".csv,text/csv"'),
    'mapping review' => str_contains($template, 'xdecaro-organizations-import-mapping'),
    'duplicate review' => str_contains($template, 'xdecaro-organizations-import-duplicate-panel'),
    'invalid review' => str_contains($template, 'xdecaro-organizations-import-invalid-panel'),
    'client csrf token' => str_contains($js, "formData.append(options.token, '1')"),
    'client no html injection' => !str_contains($js, '.innerHTML'),
    'asset registered' => str_contains($assets, 'com_xdecaroorganizations.import'),
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

echo "Organizations 1.3.0 CSV import contract OK\n";
