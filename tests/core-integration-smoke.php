<?php

$core = (string) file_get_contents(__DIR__ . '/../component/admin/src/Service/CoreIntegrationService.php');

if (!preg_match("/MINIMUM_CORE\\s*=\\s*['\"]1\\.4\\.0['\"]/", $core)) {
    fwrite(STDERR, "Missing MINIMUM_CORE 1.4.0\n");
    exit(1);
}

foreach ([
    'organizations.provider',
    'organizations.query',
    'organizations.hierarchy',
    'organizations.duplicates',
    'organizations.people_appointments',
    'CapabilityRegistry',
    'EntityReference',
] as $marker) {
    if (!str_contains($core, $marker)) {
        fwrite(STDERR, "Missing {$marker}\n");
        exit(1);
    }
}

$provider = (string) file_get_contents(__DIR__ . '/../component/admin/src/Service/OrganizationProviderService.php');
if (str_contains($provider, '#__decaro') && !str_contains($provider, '#__xdecaroorganizations_')) {
    exit(1);
}

echo "Organizations integration smoke OK\n";
