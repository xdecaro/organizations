<?php

declare(strict_types=1);

$tablePath = __DIR__ . '/../component/admin/src/Table/OrganizationTable.php';
$controllerPath = __DIR__ . '/../component/admin/src/Controller/OrganizationsController.php';

$table = file_get_contents($tablePath);
$controller = file_get_contents($controllerPath);

if ($table === false || $controller === false) {
    fwrite(STDERR, "Unable to read Organizations state-action sources.\n");
    exit(1);
}

if (!preg_match('/setColumnAlias\s*\(\s*[\'\"]published[\'\"]\s*,\s*[\'\"]state[\'\"]\s*\)/', $table)) {
    fwrite(STDERR, "OrganizationTable must alias Joomla's published column to state.\n");
    exit(1);
}

if (!preg_match('/protected\s+\$option\s*=\s*[\'\"]com_xdecaroorganizations[\'\"]\s*;/', $controller)) {
    fwrite(STDERR, "OrganizationsController must keep admin state-action redirects on com_xdecaroorganizations.\n");
    exit(1);
}

fwrite(STDOUT, "Organizations state-action contract OK.\n");
