<?php
$root = dirname(__DIR__);
$template = file_get_contents($root . '/component/admin/tmpl/organizations/default.php');

$usesTranslatedTypeKey = str_contains($template, "COM_XDECAROORGANIZATIONS_TYPE_") && str_contains($template, 'strtoupper');
$rawTypeOutput = str_contains($template, '$this->escape($item->type)');

if (!$usesTranslatedTypeKey || $rawTypeOutput) {
    fwrite(STDERR, "Organizations list must render translated type labels instead of raw stored values.\n");
    exit(1);
}

echo "Organizations list type-label contract OK\n";
