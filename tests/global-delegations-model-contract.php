<?php

declare(strict_types=1);

$path = __DIR__ . '/../component/admin/src/Model/DelegationsModel.php';
if (!is_file($path)) { fwrite(STDERR, "Missing DelegationsModel.php\n"); exit(1); }
$source = (string) file_get_contents($path);
foreach ([
    'final class DelegationsModel extends ListModel',
    '#__xdecaroorganizations_delegations', '#__xdecaroorganizations_appointments', '#__xdecaroorganizations_organizations', '#__xdecaroorganizations_bodies',
    'person_name_snapshot',
    'filter.search', 'filter.organization', 'filter.visual_status', 'filter.temporal',
    "modify('+30 days')",
    'OrganizationDelegationDomain::effectiveEnd', 'OrganizationDelegationDomain::status', 'AppointmentDomain::roleLabelKey',
    "->bind(':organizationId'",
] as $needle) { if (!str_contains($source, $needle)) { fwrite(STDERR, "DelegationsModel missing: $needle\n"); exit(1); } }
if (str_contains($source, 'setOrganizationId(')) { fwrite(STDERR, "Global DelegationsModel must not require a single organization id\n"); exit(1); }
fwrite(STDOUT, "global delegations model contract: OK\n");
