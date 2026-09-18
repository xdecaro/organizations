<?php

define('_JEXEC', 1);

$root = dirname(__DIR__);
require_once $root . '/component/admin/src/Service/OrganizationHierarchy.php';

use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

$items = [
    (object) ['id' => 4, 'parent_id' => 2, 'name' => 'Sezione Provinciale ENS Frosinone'],
    (object) ['id' => 3, 'parent_id' => 2, 'name' => 'Sezione Provinciale ENS Roma'],
    (object) ['id' => 2, 'parent_id' => 1, 'name' => 'ENS Lazio'],
    (object) ['id' => 1, 'parent_id' => 0, 'name' => 'ENS'],
    (object) ['id' => 9, 'parent_id' => 0, 'name' => 'Altra organizzazione'],
];

$path = OrganizationHierarchy::path($items, 3);
$pathNames = array_map(static fn(object $item): string => $item->name, $path);

if ($pathNames !== ['ENS', 'ENS Lazio', 'Sezione Provinciale ENS Roma']) {
    fwrite(STDERR, "Hierarchy path must resolve root-to-current organization.\n");
    exit(1);
}

$descendants = OrganizationHierarchy::descendants($items, 1);
$descendantNames = array_map(static fn(object $item): string => $item->name, $descendants);
$depths = array_map(static fn(object $item): int => (int) ($item->hierarchy_depth ?? -1), $descendants);

if ($descendantNames !== ['ENS Lazio', 'Sezione Provinciale ENS Frosinone', 'Sezione Provinciale ENS Roma']) {
    fwrite(STDERR, "Hierarchy descendants must contain only linked child organizations in tree order.\n");
    exit(1);
}

if ($depths !== [0, 1, 1]) {
    fwrite(STDERR, "Hierarchy descendant depths must be relative to the selected organization.\n");
    exit(1);
}

$cycle = [
    (object) ['id' => 10, 'parent_id' => 11, 'name' => 'Cycle A'],
    (object) ['id' => 11, 'parent_id' => 10, 'name' => 'Cycle B'],
];

if (count(OrganizationHierarchy::path($cycle, 10)) > 2) {
    fwrite(STDERR, "Hierarchy path must stop safely on corrupt cyclic data.\n");
    exit(1);
}

echo "organization hierarchy context OK\n";
