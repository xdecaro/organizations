<?php

declare(strict_types=1);

$path = __DIR__ . '/../component/admin/src/Model/AppointmentsModel.php';
if (!is_file($path)) { fwrite(STDERR, "Missing AppointmentsModel.php\n"); exit(1); }
$source = (string) file_get_contents($path);
foreach ([
    'final class AppointmentsModel extends ListModel',
    '#__xdecaroorganizations_appointments',
    '#__xdecaroorganizations_organizations',
    '#__xdecaroorganizations_bodies',
    'person_name_snapshot',
    'filter.search', 'filter.organization', 'filter.body', 'filter.role', 'filter.visual_status',
    'AppointmentDomain::status', 'AppointmentDomain::roleLabelKey',
    "->bind(':organizationId'", "->bind(':bodyId'",
] as $needle) {
    if (!str_contains($source, $needle)) { fwrite(STDERR, "AppointmentsModel missing: $needle\n"); exit(1); }
}
if (stripos($source, 'com_xdecaropeople') !== false || stripos($source, '#__xdecaropeople') !== false) {
    fwrite(STDERR, "Global appointments must not require People\n"); exit(1);
}
if (str_contains($source, 'setOrganizationId(')) { fwrite(STDERR, "Global AppointmentsModel must not require a single organization id\n"); exit(1); }
fwrite(STDOUT, "global appointments model contract: OK\n");
