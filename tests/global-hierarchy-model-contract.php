<?php

declare(strict_types=1);

define('_JEXEC', 1);
require_once __DIR__ . '/../component/admin/src/Service/OrganizationHierarchy.php';

use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

$items = [
    (object) ['id' => 1, 'parent_id' => null, 'name' => 'Root'],
    (object) ['id' => 2, 'parent_id' => 1, 'name' => 'Child'],
    (object) ['id' => 3, 'parent_id' => 3, 'name' => 'Self'],
    (object) ['id' => 4, 'parent_id' => 5, 'name' => 'Cycle A'],
    (object) ['id' => 5, 'parent_id' => 4, 'name' => 'Cycle B'],
    (object) ['id' => 6, 'parent_id' => 99, 'name' => 'Missing Parent'],
];

$diagnostics = OrganizationHierarchy::analyze($items);
foreach (['self_parent', 'cycles', 'missing_parent', 'unreachable'] as $key) {
    if (!array_key_exists($key, $diagnostics) || !is_array($diagnostics[$key])) {
        fwrite(STDERR, "Hierarchy diagnostics missing $key\n"); exit(1);
    }
}
if (!in_array(3, $diagnostics['self_parent'], true)) { fwrite(STDERR, "Self-parent not detected\n"); exit(1); }
if (!in_array(6, $diagnostics['missing_parent'], true)) { fwrite(STDERR, "Missing parent not detected\n"); exit(1); }
if (!in_array(4, $diagnostics['cycles'], true) || !in_array(5, $diagnostics['cycles'], true)) { fwrite(STDERR, "Cycle not detected\n"); exit(1); }
if (!in_array(3, $diagnostics['unreachable'], true) || !in_array(4, $diagnostics['unreachable'], true) || !in_array(5, $diagnostics['unreachable'], true) || !in_array(6, $diagnostics['unreachable'], true)) { fwrite(STDERR, "Unreachable nodes not classified\n"); exit(1); }

$modelPath = __DIR__ . '/../component/admin/src/Model/HierarchyModel.php';
if (!is_file($modelPath)) { fwrite(STDERR, "Missing HierarchyModel.php\n"); exit(1); }
$model = (string) file_get_contents($modelPath);
foreach (['OrganizationHierarchy::analyze','OrganizationHierarchy::flattenWithDepth','filter.search','filter.type','filter.structure','filter.operational','filter.roots_only'] as $needle) { if (!str_contains($model, $needle)) { fwrite(STDERR, "HierarchyModel missing: $needle\n"); exit(1); } }
fwrite(STDOUT, "global hierarchy model contract: OK\n");
