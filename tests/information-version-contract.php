<?php

$path = __DIR__ . '/../component/admin/src/Model/InformationModel.php';
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

echo "information-version contract OK\n";
