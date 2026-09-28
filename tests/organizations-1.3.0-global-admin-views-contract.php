<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
if ($version === '' || version_compare($version, '1.3.0', '<')) { fwrite(STDERR, "VERSION must be >= 1.3.0\n"); exit(1); }
foreach ([$root . '/component/xdecaroorganizations.xml', $root . '/package/pkg_organizations.xml'] as $manifest) {
    $xml = simplexml_load_file($manifest);
    if (!$xml || (string) $xml->version !== $version) { fwrite(STDERR, basename($manifest) . " version mismatch\n"); exit(1); }
}
$assets = json_decode((string) file_get_contents($root . '/component/media/joomla.asset.json'), true, 512, JSON_THROW_ON_ERROR);
if (($assets['version'] ?? '') !== $version) { fwrite(STDERR, "Asset version mismatch\n"); exit(1); }
if (!is_file($root . '/component/admin/sql/updates/mysql/1.3.0.sql')) { fwrite(STDERR, "Missing 1.3.0 SQL marker\n"); exit(1); }
foreach (['affiliations','appointments','delegations','hierarchy','bodies'] as $view) {
    $model = ucfirst($view) . 'Model.php';
    if (!is_file($root . '/component/admin/src/Model/' . $model)) { fwrite(STDERR, "Missing model $model\n"); exit(1); }
    if (!is_file($root . '/component/admin/src/View/' . ucfirst($view) . '/HtmlView.php')) { fwrite(STDERR, "Missing view $view\n"); exit(1); }
    if (!is_file($root . '/component/admin/tmpl/' . $view . '/default.php')) { fwrite(STDERR, "Missing template $view\n"); exit(1); }
}
$manifestText = (string) file_get_contents($root . '/component/xdecaroorganizations.xml');
$order = ['view="dashboard"','view="organizations"','view="affiliations"','view="appointments"','view="delegations"','view="hierarchy"','view="bodies"','view="duplicates"','view="maintenance"','view="information"'];
$last = -1;
foreach ($order as $needle) {
    $pos = strpos($manifestText, $needle, $last + 1);
    if ($pos === false || $pos <= $last) { fwrite(STDERR, "Menu order mismatch at $needle\n"); exit(1); }
    $last = $pos;
}
fwrite(STDOUT, "Organizations 1.3.0+ global admin views contract: OK\n");
