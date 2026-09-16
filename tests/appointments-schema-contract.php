<?php

$root = dirname(__DIR__);
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migrationPath = $root . '/component/admin/sql/updates/mysql/1.0.21.sql';
$migration = is_file($migrationPath) ? (string) file_get_contents($migrationPath) : '';
$tablePath = $root . '/component/admin/src/Table/OrganizationAppointmentTable.php';
$table = is_file($tablePath) ? (string) file_get_contents($tablePath) : '';

$required = [
    '#__xdecaroorganizations_appointments',
    '`person_uuid` CHAR(36) NOT NULL',
    '`person_name_snapshot` VARCHAR(255) NOT NULL',
    '`role_code` VARCHAR(50) NOT NULL',
    '`starts_on` DATE NOT NULL',
    '`planned_ends_on` DATE DEFAULT NULL',
    '`ended_on` DATE DEFAULT NULL',
    'UNIQUE KEY `idx_appointment_uuid` (`uuid`)',
    'KEY `idx_appointment_org` (`organization_id`)',
    'KEY `idx_appointment_person` (`person_uuid`)',
    'KEY `idx_appointment_org_dates` (`organization_id`,`starts_on`,`planned_ends_on`)',
    'KEY `idx_appointment_state` (`state`)',
    'CONSTRAINT `fk_xdecaroorganizations_appointment_org`',
    'ON DELETE CASCADE',
];

foreach ($required as $needle) {
    if (!str_contains($install, $needle) || !str_contains($migration, $needle)) {
        fwrite(STDERR, "Missing appointments schema fragment: {$needle}\n");
        exit(1);
    }
}

if (str_contains($install . $migration, '#__xdecaropeople_')) {
    fwrite(STDERR, "Appointments schema must not reference People tables.\n");
    exit(1);
}

if (!str_contains($table, "#__xdecaroorganizations_appointments")) {
    fwrite(STDERR, "OrganizationAppointmentTable must map the appointments table.\n");
    exit(1);
}

echo "appointments schema contract OK\n";
