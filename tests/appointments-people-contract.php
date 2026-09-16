<?php

$root = dirname(__DIR__);
$servicePath = $root . '/component/admin/src/Service/PeopleIntegrationService.php';
$service = is_file($servicePath) ? (string) file_get_contents($servicePath) : '';
$provider = (string) file_get_contents($root . '/component/admin/services/provider.php');
$component = (string) file_get_contents($root . '/component/admin/src/Extension/OrganizationsComponent.php');

$checks = [
    "bootComponent('com_xdecaropeople')" => 'Organizations must boot People through Joomla.',
    'getPersonProviderService' => 'Organizations must use the public People provider.',
    'searchPeople' => 'People adapter must expose search.',
    'getPerson' => 'People adapter must resolve a person.',
    'getPeopleByUuids' => 'People adapter must support batch resolution.',
    'catch (Throwable)' => 'People adapter must degrade safely when People is unavailable.',
];

foreach ($checks as $needle => $message) {
    if (!str_contains($service, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

if (!str_contains($provider, 'PeopleIntegrationService::class')
    || !str_contains($component, 'getPeopleIntegrationService')
    || !str_contains($component, 'setPeopleIntegrationService')) {
    fwrite(STDERR, "People integration service must be registered on OrganizationsComponent.\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/component'));
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $source = (string) file_get_contents($file->getPathname());
    if (str_contains($source, '#__xdecaropeople_')) {
        fwrite(STDERR, "Direct People table reference found in " . $file->getPathname() . "\n");
        exit(1);
    }
}

echo "appointments People integration contract OK\n";
