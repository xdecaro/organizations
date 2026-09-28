<?php
$root = dirname(__DIR__);
$template = file_get_contents($root . '/component/admin/tmpl/organization/edit.php');

$usesItemName = str_contains($template, '$this->item->name');
$escapesName = str_contains($template, '$this->escape');
$usesNewFallback = str_contains($template, "COM_XDECAROORGANIZATIONS_ORGANIZATION_NEW");
$headingBeforeEditor = strpos($template, '$this->item->name') !== false
    && strpos($template, 'id="organizationAccordion"') !== false
    && strpos($template, '$this->item->name') < strpos($template, 'id="organizationAccordion"');
$usesPeopleStyleHeading = str_contains($template, 'xdecaro-organization-heading')
    && str_contains($template, '<h2>');

if (!$usesItemName || !$escapesName || !$usesNewFallback || !$headingBeforeEditor || !$usesPeopleStyleHeading) {
    fwrite(STDERR, "Organization edit page must show the current organization name above the accordion using the shared People-style heading pattern, with a safe fallback for new records.\n");
    exit(1);
}

echo "Organizations edit-name heading contract OK\n";
