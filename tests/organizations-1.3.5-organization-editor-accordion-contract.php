<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$fail = static function (string $message): never {
    fwrite(STDERR, $message . "\n");
    exit(1);
};

$read = static function (string $path) use ($fail): string {
    if (!is_file($path)) {
        $fail("Missing file: {$path}");
    }

    return (string) file_get_contents($path);
};

$edit = $read($root . '/component/admin/tmpl/organization/edit.php');
$view = $read($root . '/component/admin/src/View/Organization/HtmlView.php');
$css = $read($root . '/component/media/css/admin.css');
$accordionJs = $read($root . '/component/media/js/organization-accordion.js');
$assetsSource = $read($root . '/component/media/joomla.asset.json');

foreach ([
    'id="organizationAccordion"',
    'data-bs-parent="#organizationAccordion"',
    '$activeTab === $sectionId',
    'aria-expanded=',
    'aria-controls=',
    'id="organization-active-tab"',
] as $marker) {
    if (!str_contains($edit, $marker)) {
        $fail("Organization accordion template missing marker: {$marker}");
    }
}

if (str_contains($edit, 'uitab.')) {
    $fail('Legacy horizontal Joomla tabs must not remain in the organization editor');
}

foreach ([
    'identity',
    'structure',
    'hierarchy',
    'affiliations',
    'contacts',
    'bodies',
    'members',
    'delegations',
    'publishing',
    'system',
] as $section) {
    if (!str_contains($edit, "'{$section}'")) {
        $fail("Organization accordion section missing: {$section}");
    }
}

foreach (['identity', 'structure', 'contacts', 'social', 'headquarters', 'publishing'] as $fieldset) {
    if (!str_contains($edit, "renderFieldset('{$fieldset}')")) {
        $fail("Existing organization fieldset was lost: {$fieldset}");
    }
}

foreach (['hierarchy', 'affiliations', 'bodies', 'members', 'delegations', 'system'] as $template) {
    if (!str_contains($edit, "loadTemplate('{$template}')")) {
        $fail("Existing organization subtemplate was lost: {$template}");
    }
}

if (!str_contains($view, "useScript('bootstrap.collapse')")) {
    $fail('Organization editor must explicitly load bootstrap.collapse');
}

if (!str_contains($css, '.xdecaro-organization-accordion')) {
    $fail('Organization accordion styling is missing');
}

foreach (['shown.bs.collapse', "url.searchParams.set('activeTab', section)"] as $marker) {
    if (!str_contains($accordionJs, $marker)) {
        $fail("Organization accordion behavior missing marker: {$marker}");
    }
}

$assets = json_decode($assetsSource, true, 512, JSON_THROW_ON_ERROR);
if (($assets['version'] ?? null) !== '1.3.5') {
    $fail('Web asset manifest version must be 1.3.5');
}

if (!str_contains($assetsSource, 'com_xdecaroorganizations.organization-accordion')) {
    $fail('Organization accordion web asset registration is missing');
}

$componentManifest = $read($root . '/component/xdecaroorganizations.xml');
$packageManifest = $read($root . '/package/pkg_organizations.xml');
if (!str_contains($componentManifest, '<version>1.3.5</version>')) {
    $fail('Component manifest version must be 1.3.5');
}

if (!str_contains($packageManifest, '<version>1.3.5</version>')) {
    $fail('Package manifest version must be 1.3.5');
}

$sqlMarker = $read($root . '/component/admin/sql/updates/mysql/1.3.5.sql');
if (!str_contains($sqlMarker, 'no database schema changes')) {
    $fail('Organizations 1.3.5 must keep an explicit no-op schema marker');
}

fwrite(STDOUT, "Organizations 1.3.5 organization editor accordion contract: OK\n");
