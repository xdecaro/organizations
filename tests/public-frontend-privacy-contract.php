<?php

$root = dirname(__DIR__);

$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migration = (string) file_get_contents($root . '/component/admin/sql/updates/mysql/1.2.0.sql');
$appointmentModel = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAppointmentModel.php');
$appointmentController = (string) file_get_contents($root . '/component/admin/src/Controller/AppointmentController.php');
$adminTemplate = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_members.php');
$adminJs = (string) file_get_contents($root . '/component/media/js/organization-edit.js');
$siteTree = '';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/component/site'));
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $siteTree .= "\n" . $file->getPathname() . "\n" . file_get_contents($file->getPathname());
}

foreach ([$install, $migration] as $schema) {
    if (!str_contains($schema, 'show_on_frontend') || !str_contains($schema, 'DEFAULT 0')) {
        fwrite(STDERR, "Public appointment visibility must exist and default to private.\n");
        exit(1);
    }
}

foreach ([$appointmentModel, $appointmentController, $adminTemplate, $adminJs] as $source) {
    if (!str_contains($source, 'show_on_frontend')) {
        fwrite(STDERR, "Appointment public visibility must be persisted end-to-end.\n");
        exit(1);
    }
}

foreach ([
    'vat_id',
    'tax_identifier',
    'pec_email',
    'created_by',
    'modified_by',
    'appointment_membership_requirement',
    'MEMBERSHIP_ELIGIBILITY',
    'MembershipIntegrationService',
] as $forbidden) {
    if (str_contains($siteTree, $forbidden)) {
        fwrite(STDERR, "Public frontend must not expose or depend on sensitive/internal field {$forbidden}.\n");
        exit(1);
    }
}

if (!str_contains($siteTree, "show_on_frontend") || !str_contains($siteTree, "a.show_on_frontend")) {
    fwrite(STDERR, "Public person/delegation output must be gated by explicit appointment visibility.\n");
    exit(1);
}

echo "Organizations public frontend privacy contract OK\n";
