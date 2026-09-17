<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));

if ($version !== '1.0.26') {
    fwrite(STDERR, "Organizations canonical package migration must stay on 1.0.26.\n");
    exit(1);
}

$requiredFiles = [
    $root . '/package/pkg_organizations.xml',
    $root . '/package/script.php',
    $root . '/updates/pkg_organizations.xml',
    $root . '/build/build.sh',
];
foreach ($requiredFiles as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing Organizations canonical package file: {$path}\n");
        exit(1);
    }
}

$manifest = (string) file_get_contents($root . '/package/pkg_organizations.xml');
$installer = (string) file_get_contents($root . '/package/script.php');
$feed = (string) file_get_contents($root . '/updates/pkg_organizations.xml');
$build = (string) file_get_contents($root . '/build/build.sh');

$checks = [
    [$manifest, '<packagename>organizations</packagename>', 'Organizations package must use packagename organizations.'],
    [$manifest, 'updates/pkg_organizations.xml', 'Organizations package must register the canonical update feed.'],
    [$feed, '<element>pkg_organizations</element>', 'Organizations update feed must identify pkg_organizations.'],
    [$build, 'pkg_organizations_${VERSION}.zip', 'Organizations build must create pkg_organizations_VERSION.zip.'],
    [$installer, 'pkg_organizationsInstallerScript', 'Organizations installer class must use the canonical package identity.'],
    [$installer, 'pkg_xdecaroorganizations', 'Organizations installer must recognize the legacy package during migration.'],
    [$installer, 'pkg_core', 'Organizations Core detection must recognize the canonical Core package.'],
    [$installer, 'pkg_xdecarocore', 'Organizations Core detection must retain legacy Core fallback during rollout.'],
    [$installer, 'package_id', 'Organizations package migration must verify child ownership before retiring the legacy package.'],
];

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Organizations 1.0.26 canonical package contract OK\n";
