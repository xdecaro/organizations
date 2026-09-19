<?php

$root = dirname(__DIR__);
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationDelegationModel.php');
$list = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationDelegationsModel.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_delegations.php');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');
$appointmentModel = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAppointmentModel.php');

foreach ([
    'planned_ends_on',
    'Delegation cannot start after the linked appointment boundary.',
    'Delegation cannot end after the linked appointment boundary.',
] as $needle) {
    if (!str_contains($model, $needle)) {
        fwrite(STDERR, "Delegation save validation missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'appointment_planned_ends_on',
    'appointment_ended_on',
    'effective_end',
] as $needle) {
    if (!str_contains($list, $needle)) {
        fwrite(STDERR, "Delegation list must expose effective mandate boundary: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'data-planned-ends-on',
    'COM_XDECAROORGANIZATIONS_DELEGATION_MANDATE_LIMIT',
    'effective_end',
] as $needle) {
    if (!str_contains($template, $needle)) {
        fwrite(STDERR, "Delegation UI must show mandate boundary: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'dataset.plannedEndsOn',
    'delegationEndsOn.max',
] as $needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "Delegation editor must constrain end date to mandate boundary: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'endDelegations',
    'transactionStart',
    'transactionCommit',
] as $needle) {
    if (!str_contains($appointmentModel, $needle)) {
        fwrite(STDERR, "Appointment termination must close linked delegations transactionally: {$needle}.\n");
        exit(1);
    }
}

echo "delegation mandate boundary contract OK\n";
