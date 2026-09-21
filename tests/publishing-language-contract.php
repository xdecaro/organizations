<?php

$root = dirname(__DIR__);
$form = (string) file_get_contents($root . '/component/admin/forms/organization.xml');
$table = (string) file_get_contents($root . '/component/admin/src/Table/OrganizationTable.php');
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migration = (string) file_get_contents($root . '/component/admin/sql/updates/mysql/1.2.3.sql');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');

$xml = simplexml_load_string($form);
if ($xml === false) {
    fwrite(STDERR, "Organization form XML is invalid.\n");
    exit(1);
}

$publishing = $xml->xpath('/form/fieldset[@name="publishing"]/field[@name="language"]');
$contacts = $xml->xpath('/form/fieldset[@name="contacts"]/field[@name="language"]');

if (count($publishing) !== 1 || count($contacts) !== 0) {
    fwrite(STDERR, "Organization language must exist only in the Publishing fieldset.\n");
    exit(1);
}

$field = $publishing[0];
if ((string) $field['type'] !== 'contentlanguage' || (string) $field['default'] !== '*') {
    fwrite(STDERR, "Publishing language must use contentlanguage with default '*'.\n");
    exit(1);
}

$allOption = $field->xpath('./option[@value="*"]');
if (count($allOption) !== 1 || trim((string) $allOption[0]) !== 'JALL') {
    fwrite(STDERR, "Publishing language must expose an explicit All languages option.\n");
    exit(1);
}

foreach ([
    "`language` VARCHAR(16) NOT NULL DEFAULT '*'",
    "SET `language` = '*'",
    "`language` IS NULL OR TRIM(`language`) = ''",
    "MODIFY `language` VARCHAR(16) NOT NULL DEFAULT '*'",
] as $needle) {
    $source = str_starts_with($needle, 'SET ') || str_starts_with($needle, "`language` IS") || str_starts_with($needle, 'MODIFY ')
        ? $migration
        : $install;

    if (!str_contains($source, $needle)) {
        fwrite(STDERR, "Language persistence contract missing {$needle}.\n");
        exit(1);
    }
}

foreach ([
    "$this->language = $language !== '' ? $language : '*'",
] as $needle) {
    if (!str_contains($table, $needle)) {
        fwrite(STDERR, "Organization table language normalization missing {$needle}.\n");
        exit(1);
    }
}

if (str_contains($js, "country.value === 'IT'") || str_contains($js, "language.value = 'it-IT'")) {
    fwrite(STDERR, "Country selection must not mutate publishing language.\n");
    exit(1);
}

echo "Organizations publishing language contract OK\n";
