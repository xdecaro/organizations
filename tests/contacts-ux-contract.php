<?php
$root = dirname(__DIR__);
$form = file_get_contents($root . '/component/admin/forms/organization.xml');
$template = file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$css = file_get_contents($root . '/component/media/css/admin.css');
$assets = file_get_contents($root . '/component/media/joomla.asset.json');
$view = file_get_contents($root . '/component/admin/src/View/Organization/HtmlView.php');
$manifest = file_get_contents($root . '/component/xdecaroorganizations.xml');
$installSql = file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$updateSql = file_get_contents($root . '/component/admin/sql/updates/mysql/1.2.13.sql');
$siteModel = file_get_contents($root . '/component/site/src/Model/OrganizationModel.php');
$siteTemplate = file_get_contents($root . '/component/site/tmpl/organization/default.php');
$jsPath = $root . '/component/media/js/organization-edit.js';
$js = is_file($jsPath) ? file_get_contents($jsPath) : '';

$formXml = simplexml_load_string($form);
$identityCountry = $formXml !== false ? $formXml->xpath('./fieldset[@name="identity"]/field[@name="country_code"]') : [];
$contactsCountry = $formXml !== false ? $formXml->xpath('./fieldset[@name="contacts"]/field[@name="country_code"]') : [];
$headquartersCountry = $formXml !== false ? $formXml->xpath('./fieldset[@name="headquarters"]/field[@name="country_code"]') : [];
$identityLogo = $formXml !== false ? $formXml->xpath('./fieldset[@name="identity"]/field[@name="logo"]') : [];
$contactsLogo = $formXml !== false ? $formXml->xpath('./fieldset[@name="contacts"]/field[@name="logo"]') : [];
$headquarters = $formXml !== false ? $formXml->xpath('./fieldset[@name="headquarters"]') : [];
$social = $formXml !== false ? $formXml->xpath('./fieldset[@name="social"]') : [];
$socialFields = $formXml !== false ? $formXml->xpath('./fieldset[@name="social"]/field') : [];

$countryField = str_contains($form, 'addfieldprefix="xdecaro\\Component\\Organizations\\Administrator\\Field"')
    && count($identityCountry) === 1
    && count($contactsCountry) === 0
    && count($headquartersCountry) === 0
    && str_contains($form, 'code="alpha2"')
    && str_contains($form, 'layout="joomla.form.field.list-fancy-select"');

$identityLayout = count($identityLogo) === 1
    && count($contactsLogo) === 0
    && count($headquarters) === 1
    && count($social) === 1
    && count($socialFields) === 5
    && str_contains($form, 'name="facebook_url"')
    && str_contains($form, 'name="instagram_url"')
    && str_contains($form, 'name="youtube_url"')
    && str_contains($form, 'name="linkedin_url"')
    && str_contains($form, 'name="tiktok_url"')
    && str_contains($template, "renderFieldset('contacts')")
    && str_contains($template, "renderFieldset('social')")
    && str_contains($template, "renderFieldset('headquarters')")
    && str_contains($template, "COM_XDECAROORGANIZATIONS_FIELDSET_SOCIAL")
    && str_contains($template, "COM_XDECAROORGANIZATIONS_FIELDSET_HEADQUARTERS");

$peopleStyleHeading = str_contains($template, 'xdecaro-organizations-organization-edit')
    && str_contains($template, 'xdecaro-organization-heading')
    && str_contains($template, '<h2>')
    && str_contains($css, '.xdecaro-organizations-organization-edit .xdecaro-organization-heading')
    && str_contains($css, 'font-size: 1.35rem')
    && str_contains($css, 'font-weight: 600');

$countryClass = is_file($root . '/component/admin/src/Field/CountryField.php');
$countryMetadata = is_file($root . '/component/admin/src/Service/CountryMetadata.php');

$socialPersistence = str_contains($installSql, '`facebook_url` VARCHAR(512)')
    && str_contains($installSql, '`instagram_url` VARCHAR(512)')
    && str_contains($installSql, '`youtube_url` VARCHAR(512)')
    && str_contains($installSql, '`linkedin_url` VARCHAR(512)')
    && str_contains($installSql, '`tiktok_url` VARCHAR(512)')
    && str_contains($updateSql, 'ADD COLUMN `facebook_url` VARCHAR(512)')
    && str_contains($updateSql, 'ADD COLUMN `instagram_url` VARCHAR(512)')
    && str_contains($updateSql, 'ADD COLUMN `youtube_url` VARCHAR(512)')
    && str_contains($updateSql, 'ADD COLUMN `linkedin_url` VARCHAR(512)')
    && str_contains($updateSql, 'ADD COLUMN `tiktok_url` VARCHAR(512)');

$publicSocial = str_contains($siteModel, "'o.facebook_url'")
    && str_contains($siteModel, "'o.instagram_url'")
    && str_contains($siteModel, "'o.youtube_url'")
    && str_contains($siteModel, "'o.linkedin_url'")
    && str_contains($siteModel, "'o.tiktok_url'")
    && str_contains($siteTemplate, "'field' => 'facebook_url'")
    && str_contains($siteTemplate, 'COM_XDECAROORGANIZATIONS_SITE_SOCIAL_PROFILE');

$languageIndependent = str_contains($assets, 'com_xdecaroorganizations.organization-edit')
    && str_contains($view, "useScript('com_xdecaroorganizations.organization-edit')")
    && str_contains($manifest, '<folder>js</folder>')
    && !str_contains($js, "country.value === 'IT'")
    && !str_contains($js, "language.value = 'it-IT'");

if (!$countryField || !$identityLayout || !$peopleStyleHeading || !$countryClass || !$countryMetadata || !$socialPersistence || !$publicSocial || !$languageIndependent) {
    fwrite(STDERR, "Organizations contacts UX must keep Country and Logo in Identity, split Contacts, Social and Headquarters, persist the five social URLs, expose them publicly, use a searchable Country field, and keep country independent from publishing language.\n");
    exit(1);
}

echo "Organizations contacts UX contract OK\n";
