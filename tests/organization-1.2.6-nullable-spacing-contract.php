<?php

$root = dirname(__DIR__);

$table = (string) file_get_contents($root . '/component/admin/src/Table/OrganizationTable.php');
$affiliations = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_affiliations.php');

$checks = [
    'nullable store override' => str_contains($table, 'public function store($updateNulls = true)'),
    'nullable store parent call' => str_contains($table, 'return parent::store($updateNulls);'),
    'affiliation modal padding' => str_contains($affiliations, 'modal-body p-4'),
    'affiliation vertical spacing' => str_contains($affiliations, 'row gx-3 gy-4'),
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$name}\n");
        exit(1);
    }
}

echo "Organizations 1.2.6 nullable persistence and affiliation spacing contract OK\n";
