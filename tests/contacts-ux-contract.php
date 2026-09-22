<?php
$root = dirname(__DIR__);
$form = file_get_contents($root . '/component/admin/forms/organization.xml');
$template = file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$css = file_get_contents($root . '/component/media/css/admin.css');
$assets = file_get_contents($root . '/component/media/joomla.asset.json');
$view = file_get_contents($root . '/component/admin/src/View/Organization/HtmlView.php');
$manifest = file_get_contents($root . '/component/xdecaroorganizations.xml');
$jsPath = $root . '/component/media/js/organization-edit.js';
$js = is_file($jsPath) ? file_get_contents($jsPath) : '';

$countryField = str_contains($form, 'addfieldprefix="xdecaro\\Component\\Organizations\\Administrator\\Field"')
    && str_contains($form, 'name="country_code" type="Country"')
    && str_contains($form, 'code="alpha2"')
    && str_contains($form, 'layout="joomla.form.field.list-fancy-select"');

$peopleStyleHeading = str_contains($template, 'xdecaro-organizations-organization-edit')
    && str_contains($template, 'xdecaro-organization-heading')
    && str_contains($template, '<h2>')
    && str_contains($css, '.xdecaro-organizations-organization-edit .xdecaro-organization-heading')
    && str_contains($css, 'font-size: 1.35rem')
    && str_contains($css, 'font-weight: 600');

$countryClass = is_file($root . '/component/admin/src/Field/CountryField.php');
$countryMetadata = is_file($root . '/component/admin/src/Service/CountryMetadata.php');

$languageIndependent = str_contains($assets, 'com_xdecaroorganizations.organization-edit')
    && str_contains($view, "useScript('com_xdecaroorganizations.organization-edit')")
    && str_contains($manifest, '<folder>js</folder>')
    && !str_contains($js, "country.value === 'IT'")
    && !str_contains($js, "language.value = 'it-IT'");

if (!$countryField || !$peopleStyleHeading || !$countryClass || !$countryMetadata || !$languageIndependent) {
    fwrite(STDERR, "Organizations contacts UX must match People heading style, use a searchable Country field, and keep country independent from publishing language.\n");
    exit(1);
}

echo "Organizations contacts UX contract OK\n";
