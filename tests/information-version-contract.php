<?php

$root = dirname(__DIR__);
$path = $root . '/component/admin/src/Model/InformationModel.php';
$source = file_get_contents($path);

if ($source === false) {
    fwrite(STDERR, "Unable to read InformationModel.php\n");
    exit(1);
}

$checks = [
    "use Joomla\\CMS\\Extension\\ExtensionHelper;" => 'InformationModel must read the installed component extension record.',
    "use Joomla\\Registry\\Registry;" => 'InformationModel must parse manifest_cache for the installed version.',
    "ExtensionHelper::getExtensionRecord('com_xdecaroorganizations', 'component', 1)" => 'InformationModel must query the installed Organizations component record.',
    "'component_version' => (string) \$manifest->get('version', '')" => 'InformationModel must report the installed manifest version dynamically.',
];

foreach ($checks as $needle => $message) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

if (preg_match("/'component_version'\\s*=>\\s*'\\d+\\.\\d+\\.\\d+'/", $source)) {
    fwrite(STDERR, "InformationModel must not hard-code the Organizations version.\n");
    exit(1);
}

$version = trim((string) file_get_contents($root . '/VERSION'));
$assetSource = (string) file_get_contents($root . '/component/media/joomla.asset.json');
$asset = json_decode($assetSource, true);
$build = (string) file_get_contents($root . '/build/build.sh');

if (!is_array($asset) || ($asset['version'] ?? '') !== $version) {
    fwrite(STDERR, "joomla.asset.json version must match VERSION so Joomla/browser asset cache changes with each release.\n");
    exit(1);
}

$assetsByName = [];
foreach (($asset['assets'] ?? []) as $item) {
    if (is_array($item) && isset($item['name'])) {
        $assetsByName[(string) $item['name']] = $item;
    }
}

$adminStyleUri = (string) ($assetsByName['com_xdecaroorganizations.admin']['uri'] ?? '');
$editScriptUri = (string) ($assetsByName['com_xdecaroorganizations.organization-edit']['uri'] ?? '');

if ($adminStyleUri !== 'com_xdecaroorganizations/admin.css') {
    fwrite(STDERR, "Organizations admin style URI must omit the css subdirectory because Joomla adds it for style assets.\n");
    exit(1);
}

if ($editScriptUri !== 'com_xdecaroorganizations/organization-edit.js') {
    fwrite(STDERR, "Organizations edit script URI must omit the js subdirectory because Joomla adds it for script assets.\n");
    exit(1);
}

if (!str_contains($build, 'joomla.asset.json')
    || !str_contains($build, '$data["version"] = $version;')
    || !str_contains($build, '"$WORK/component/media/joomla.asset.json" "$VERSION"')) {
    fwrite(STDERR, "Build must synchronize joomla.asset.json version from VERSION before packaging.\n");
    exit(1);
}

echo "information-version contract OK\n";
