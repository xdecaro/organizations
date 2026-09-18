<?php

$root = dirname(__DIR__);
$domain = (string) file_get_contents($root . '/component/admin/src/Service/AppointmentDomain.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_members.php');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAppointmentModel.php');

foreach ([
    "return 'scheduled';",
    "starts_on",
] as $needle) {
    if (!str_contains($domain, $needle)) {
        fwrite(STDERR, "AppointmentDomain must classify future appointments as scheduled: {$needle}\n");
        exit(1);
    }
}

foreach ([
    '$scheduled = [];',
    "COM_XDECAROORGANIZATIONS_MEMBERS_SCHEDULED",
    "COM_XDECAROORGANIZATIONS_STATUS_SCHEDULED",
] as $needle) {
    if (!str_contains($template, $needle)) {
        fwrite(STDERR, "Members UI must render scheduled appointments separately: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($model, "['active', 'scheduled']")) {
    fwrite(STDERR, "Scheduled appointments must remain deletable before they begin.\n");
    exit(1);
}

echo "Organizations scheduled appointments contract OK\n";
