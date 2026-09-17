<?php

$root = dirname(__DIR__);
$domain = (string) file_get_contents($root . '/component/admin/src/Service/AppointmentDomain.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_members.php');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAppointmentModel.php');

foreach ([
    "'representative'",
    "'commissioner'",
    "'vice_commissioner'",
    "'delegate'",
    "'control_member'",
    "'administrative_secretary'",
] as $role) {
    if (!str_contains($domain, $role)) {
        fwrite(STDERR, "Missing generic organization role {$role}.\n");
        exit(1);
    }
}

if (!str_contains($template, 'appointment-body-id')
    || !str_contains($template, 'APPOINTMENT_BODY')
    || !str_contains($model, "'body_id'")) {
    fwrite(STDERR, "Appointments must support choosing an organization body.\n");
    exit(1);
}

echo "Organizations appointment bodies/roles contract OK\n";
