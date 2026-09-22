<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$form = (string) file_get_contents($root . '/component/admin/forms/organization.xml');
$table = (string) file_get_contents($root . '/component/admin/src/Table/OrganizationTable.php');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationModel.php');
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migration = (string) file_get_contents($root . '/component/admin/sql/updates/mysql/1.2.4.sql');
$it = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$en = (string) file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini');
$siteCss = (string) file_get_contents($root . '/component/media/css/site.css');

$checks = [
    [$form, 'name="status_since" type="calendar"', 'Status since must remain a Joomla calendar field.'],
    [$form, 'hint="COM_XDECAROORGANIZATIONS_DATE_PLACEHOLDER"', 'Status since must expose an explicit day/month/year placeholder without changing Joomla date storage.'],
    [$form, 'name="province" type="text"', 'Contacts must expose a separate province field.'],
    [$form, 'name="region" type="text"', 'Contacts must retain a separate region field.'],
    [$install, '`province` VARCHAR(190) DEFAULT NULL', 'Fresh installs must include the province column.'],
    [$migration, 'ADD COLUMN `province` VARCHAR(190) NULL AFTER `city`', 'The 1.2.4 migration must add province without rewriting region data.'],
    [$table, "'province'", 'OrganizationTable must normalize province.'],
    [$model, "'province'", 'Sensitive-field ACL handling must include province.'],
    [$it, 'COM_XDECAROORGANIZATIONS_FIELDSET_MEMBERS="Incarichi"', 'Italian tab label must clarify that records are appointments, not members.'],
    [$en, 'COM_XDECAROORGANIZATIONS_FIELDSET_MEMBERS="Appointments"', 'English tab label must clarify that records are appointments.'],
    [$it, 'COM_XDECAROORGANIZATIONS_FIELD_REGION="Regione"', 'Italian region label must no longer combine province and region.'],
    [$it, 'COM_XDECAROORGANIZATIONS_FIELD_PROVINCE="Provincia"', 'Italian province label is required.'],
    [$siteCss, '.xo-badges .text-bg-secondary', 'Public type badges need an explicit theme-aware contrast rule.'],
];

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

foreach ([
    'AUTONOMY_LEGAL',
    'AUTONOMY_MANAGEMENT',
    'AUTONOMY_ADMINISTRATIVE',
    'AUTONOMY_TAX',
    'AUTONOMY_FISCAL',
] as $suffix) {
    if (!str_contains($form, 'description="COM_XDECAROORGANIZATIONS_FIELD_' . $suffix . '_DESC"')) {
        fwrite(STDERR, "Autonomy switch {$suffix} must have explanatory help text.\n");
        exit(1);
    }
}

echo "Organizations 1.2.4 editor UX contract OK\n";
