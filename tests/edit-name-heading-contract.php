<?php
$root = dirname(__DIR__);
$template = file_get_contents($root . '/component/admin/tmpl/organization/edit.php');

$usesItemName = str_contains($template, '$this->item->name');
$escapesName = str_contains($template, '$this->escape');
$usesNewFallback = str_contains($template, "COM_XDECAROORGANIZATIONS_ORGANIZATION_NEW");
$headingBeforeTabs = strpos($template, '$this->item->name') !== false
    && strpos($template, "uitab.startTabSet") !== false
    && strpos($template, '$this->item->name') < strpos($template, "uitab.startTabSet");
$usesLargerHeading = str_contains($template, '<h2 class="h3 mb-0">');

if (!$usesItemName || !$escapesName || !$usesNewFallback || !$headingBeforeTabs || !$usesLargerHeading) {
    fwrite(STDERR, "Organization edit page must show a larger current organization name above the tabs, with a safe fallback for new records.\n");
    exit(1);
}

echo "Organizations edit-name heading contract OK\n";
