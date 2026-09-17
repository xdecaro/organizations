<?php

$root = dirname(__DIR__);
$corePath = $root . '/component/admin/src/Service/CoreIntegrationService.php';
$componentPath = $root . '/component/admin/src/Extension/OrganizationsComponent.php';
$providerPath = $root . '/component/admin/services/provider.php';
$accessPath = $root . '/component/admin/access.xml';
$servicePath = $root . '/component/admin/src/Service/PersonAppointmentsService.php';

$core = (string) file_get_contents($corePath);
$component = (string) file_get_contents($componentPath);
$provider = (string) file_get_contents($providerPath);
$access = (string) file_get_contents($accessPath);

if (!is_file($servicePath)) {
    fwrite(STDERR, "Missing PersonAppointmentsService.php.\n");
    exit(1);
}

$service = (string) file_get_contents($servicePath);

$required = [
    [$core, 'organizations.people_appointments', 'Missing person-appointments capability.'],
    [$component, 'getPersonAppointmentsService', 'Missing component accessor.'],
    [$provider, 'PersonAppointmentsService::class', 'Missing Joomla DI registration for person appointments service.'],
    [$access, 'organizations.view_appointments', 'Missing narrow appointment-history ACL.'],
    [$service, 'getAppointmentsByPersonUuid', 'Missing person appointment lookup.'],
    [$service, 'AppointmentDomain::status', 'Status must reuse Organizations domain logic.'],
    [$service, 'AppointmentDomain::roleLabelKey', 'Role labels must reuse Organizations domain logic.'],
    [$service, "authorise('organizations.view_appointments'", 'Service must enforce the narrow appointment-history ACL.'],
    [$service, "authorise('core.admin'", 'Service must allow Joomla core.admin.'],
    [$service, '#__xdecaroorganizations_appointments', 'Service must query the Organizations-owned appointments table.'],
    [$service, '#__xdecaroorganizations_organizations', 'Service must join the Organizations-owned organization table.'],
    [$service, "'appointment_id'", 'Public contract is missing appointment_id.'],
    [$service, "'appointment_uuid'", 'Public contract is missing appointment_uuid.'],
    [$service, "'organization_id'", 'Public contract is missing organization_id.'],
    [$service, "'organization_uuid'", 'Public contract is missing organization_uuid.'],
    [$service, "'organization_name'", 'Public contract is missing organization_name.'],
    [$service, "'role_code'", 'Public contract is missing role_code.'],
    [$service, "'role_custom'", 'Public contract is missing role_custom.'],
    [$service, "'role_label_key'", 'Public contract is missing role_label_key.'],
    [$service, "'starts_on'", 'Public contract is missing starts_on.'],
    [$service, "'planned_ends_on'", 'Public contract is missing planned_ends_on.'],
    [$service, "'ended_on'", 'Public contract is missing ended_on.'],
    [$service, "'end_reason'", 'Public contract is missing end_reason.'],
    [$service, "'visual_status'", 'Public contract is missing visual_status.'],
    [$service, "'is_current'", 'Public contract is missing is_current.'],
];

foreach ($required as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

foreach (['notes', 'end_note', 'vat_id', 'tax_identifier', 'address_line', 'postal_code', 'pec_email'] as $forbidden) {
    if (preg_match('/[\'\"]' . preg_quote($forbidden, '/') . '[\'\"]\s*(?:=>|,)/', $service)) {
        fwrite(STDERR, "Public person-appointments service must not expose/select forbidden field: {$forbidden}.\n");
        exit(1);
    }
}

if (!preg_match('/a\.person_uuid[^\n]*:personUuid|person_uuid[^\n]*:personUuid/s', $service)) {
    fwrite(STDERR, "Person appointment lookup must filter by a bound person UUID.\n");
    exit(1);
}

if (!str_contains($service, "return [];")) {
    fwrite(STDERR, "Invalid or empty person UUID must fail closed with an empty result.\n");
    exit(1);
}

echo "Organizations person appointments provider contract OK\n";
