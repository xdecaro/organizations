<?php

declare(strict_types=1);

$path = __DIR__ . '/../component/admin/src/Model/BodiesModel.php';
if (!is_file($path)) { fwrite(STDERR, "Missing BodiesModel.php\n"); exit(1); }
$source = (string) file_get_contents($path);
foreach ([
    'final class BodiesModel extends ListModel',
    '#__xdecaroorganizations_bodies', '#__xdecaroorganizations_organizations', '#__xdecaroorganizations_appointments',
    'parent_name', 'appointment_count', 'COUNT(*)',
    'filter.search', 'filter.organization', 'filter.body_type', 'filter.visual_status',
    'OrganizationBodyDomain::status', 'OrganizationBodyDomain::typeLabelKey',
    "->bind(':organizationId'",
] as $needle) { if (!str_contains($source, $needle)) { fwrite(STDERR, "BodiesModel missing: $needle\n"); exit(1); } }
if (str_contains($source, 'setOrganizationId(')) { fwrite(STDERR, "Global BodiesModel must not require a single organization id\n"); exit(1); }
fwrite(STDOUT, "global bodies model contract: OK\n");
