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

$formXml = simplexml_load_string($form);
$identityCountry = $formXml !== false ? $formXml->xpath('./fieldset[@name="identity"]/field[@name="country_code"]') : [];
$contactsCountry = $formXml !== false ? $formXml->xpath('./fieldset[@name="contacts"]/field[@name="country_code"]') : [];
$headquartersCountry = $formXml !== false ? $formXml->xpath('./fieldset[@name="headquarters"]/field[@name="country_code"]') : [];
$identityLogo = $formXml !== false ? $formXml->xpath('./fieldset[@name="identity"]/field[@name="logo"]') : [];
$contactsLogo = $formXml !== false ? $formXml->xpath('./fieldset[@name="contacts"]/field[@name="logo"]') : [];
$headquarters = $formXml !== false ? $formXml->xpath('./fieldset[@name="headquarters"]') : [];

$countryField = str_contains($form, 'addfieldprefix="xdecaro\\Component\\Organizations\\Administrator\\Field"')
    && count($identityCountry) === 1
    && count($contactsCountry) === 0
    && count($headquartersCountry) === 0
    && str_contains($form, 'code="alpha2"')
    && str_contains($form, 'layout="joomla.form.field.list-fancy-select"');

$identityLayout = count($identityLogo) === 1
    && count($contactsLogo) === 0
    && count($headquarters) === 1
    && str_contains($template, "renderFieldset('contacts')")
    && str_contains($template, "renderFieldset('headquarters')")
    && str_contains($template, "COM_XDECAROORGANIZATIONS_FIELDSET_HEADQUARTERS");

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

if (!$countryField || !$identityLayout || !$peopleStyleHeading || !$countryClass || !$countryMetadata || !$languageIndependent) {
    fwrite(STDERR, "Organizations contacts UX must keep Country and Logo in Identity, split Contacts from Headquarters, use a searchable Country field, and keep country independent from publishing language.\n");
    exit(1);
}

echo "Organizations contacts UX contract OK\n";
