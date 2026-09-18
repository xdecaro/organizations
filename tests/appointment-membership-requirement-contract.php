<?php

$root = dirname(__DIR__);
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migrationPath = $root . '/component/admin/sql/updates/mysql/1.1.0.sql';
$migration = is_file($migrationPath) ? (string) file_get_contents($migrationPath) : '';
$form = (string) file_get_contents($root . '/component/admin/forms/organization.xml');
$table = (string) file_get_contents($root . '/component/admin/src/Table/OrganizationTable.php');
$policyPath = $root . '/component/admin/src/Service/AppointmentMembershipPolicyService.php';
$policy = is_file($policyPath) ? (string) file_get_contents($policyPath) : '';
$integrationPath = $root . '/component/admin/src/Service/MembershipIntegrationService.php';
$integration = is_file($integrationPath) ? (string) file_get_contents($integrationPath) : '';
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAppointmentModel.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_members.php');

foreach ([
    "`appointment_membership_requirement` VARCHAR(32) NOT NULL DEFAULT 'inherit'",
] as $needle) {
    if (!str_contains($install, $needle) || !str_contains($migration, $needle)) {
        fwrite(STDERR, "Missing appointment membership requirement schema: {$needle}\n");
        exit(1);
    }
}

foreach ([
    'name="appointment_membership_requirement"',
    'value="inherit"',
    'value="none"',
    'value="active_member"',
    'value="active_member_fee_current"',
] as $needle) {
    if (!str_contains($form, $needle)) {
        fwrite(STDERR, "Missing membership requirement form option: {$needle}\n");
        exit(1);
    }
}

if (!str_contains($table, 'appointment_membership_requirement')) {
    fwrite(STDERR, "Organization table must validate membership requirement.\n");
    exit(1);
}

foreach ([
    'resolveRequirement',
    "'inherit'",
    "'active_member'",
    "'active_member_fee_current'",
] as $needle) {
    if (!str_contains($policy, $needle)) {
        fwrite(STDERR, "Appointment membership policy missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'getMembershipPersonHistoryService',
    'getHistoryByPersonUuid',
    "'not_member'",
    "'inactive_member'",
    "'fee_not_current'",
    "'eligible'",
    "'unavailable'",
] as $needle) {
    if (!str_contains($integration, $needle)) {
        fwrite(STDERR, "Membership integration missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'assertMembershipEligibility',
    'getAppointmentMembershipPolicyService',
] as $needle) {
    if (!str_contains($model, $needle)) {
        fwrite(STDERR, "Appointment save must enforce configured membership requirement on new/person-changed appointments: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    'data-membership-eligibility',
    'COM_XDECAROORGANIZATIONS_MEMBERSHIP_REQUIREMENT',
] as $needle) {
    if (!str_contains($template, $needle)) {
        fwrite(STDERR, "Members UI must expose membership eligibility: {$needle}.\n");
        exit(1);
    }
}

echo "Organizations appointment membership requirement contract OK\n";
