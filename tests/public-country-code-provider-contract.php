<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$provider = (string) file_get_contents($root . '/component/admin/src/Service/OrganizationProviderService.php');

if (!str_contains($provider, "'o.country_code','o.state','o.access','o.language'")) {
    fwrite(STDERR, "country_code must be part of the normal Organizations provider payload.\n");
    exit(1);
}

if (preg_match("/array_merge\(\$c,\[[^\]]*'o\.country_code'/s", $provider)) {
    fwrite(STDERR, "country_code must not require sensitive-data access.\n");
    exit(1);
}

foreach (['o.vat_id', 'o.tax_identifier', 'o.pec_email', 'o.address_line', 'o.postal_code', 'o.city', 'o.region', 'o.notes'] as $field) {
    if (!str_contains($provider, $field)) {
        fwrite(STDERR, "Sensitive provider field unexpectedly missing: {$field}\n");
        exit(1);
    }
}

echo "Organizations public country code contract OK\n";
