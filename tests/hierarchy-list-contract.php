<?php

define('_JEXEC', 1);

$root = dirname(__DIR__);
$model = file_get_contents($root . '/component/admin/src/Model/OrganizationsModel.php');
$template = file_get_contents($root . '/component/admin/tmpl/organizations/default.php');
$css = file_get_contents($root . '/component/media/css/admin.css');
require_once $root . '/component/admin/src/Service/OrganizationHierarchy.php';

use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

$items = [
    (object) ['id' => 3, 'parent_id' => 2, 'name' => 'Sezione Provinciale ENS Roma'],
    (object) ['id' => 4, 'parent_id' => 0, 'name' => 'Zeta'],
    (object) ['id' => 1, 'parent_id' => 0, 'name' => 'ENS'],
    (object) ['id' => 2, 'parent_id' => 1, 'name' => 'Consiglio Regionale ENS Lazio'],
];

$ordered = OrganizationHierarchy::order($items);
$names = array_map(static fn(object $item): string => $item->name, $ordered);
$depths = array_map(static fn(object $item): int => (int) $item->hierarchy_depth, $ordered);

$expectedNames = ['ENS', 'Consiglio Regionale ENS Lazio', 'Sezione Provinciale ENS Roma', 'Zeta'];
$expectedDepths = [0, 1, 2, 0];

$cycleItems = [
    (object) ['id' => 10, 'parent_id' => 11, 'name' => 'Cycle A'],
    (object) ['id' => 11, 'parent_id' => 10, 'name' => 'Cycle B'],
];
$cycleOrdered = OrganizationHierarchy::order($cycleItems);

$orphan = OrganizationHierarchy::order([
    (object) ['id' => 20, 'parent_id' => 999, 'name' => 'Filtered child'],
]);

$modelUsesHierarchy = str_contains($model, 'OrganizationHierarchy::order')
    && str_contains($model, 'public function getItems()')
    && str_contains($model, 'array_slice');

$templateShowsHierarchy = str_contains($template, 'hierarchy_depth')
    && str_contains($template, 'xdecaro-organization-tree-name')
    && str_contains($template, '--xdecaro-org-indent-desktop')
    && str_contains($template, '--xdecaro-org-indent-mobile')
    && str_contains($template, '$depth * 1.5')
    && str_contains($template, '$depth * 1')
    && str_contains($template, '↳');

$cssIndentsByDepth = str_contains($css, '.xdecaro-organization-tree-name')
    && str_contains($css, 'var(--xdecaro-org-indent-desktop')
    && str_contains($css, 'var(--xdecaro-org-indent-mobile')
    && !str_contains($css, 'calc(var(--xdecaro-org-depth');

$behaviorOk = $names === $expectedNames
    && $depths === $expectedDepths
    && count($cycleOrdered) === 2
    && count(array_unique(array_map(static fn(object $item): int => (int) $item->id, $cycleOrdered))) === 2
    && count($orphan) === 1
    && (int) $orphan[0]->hierarchy_depth === 0;

if (!$behaviorOk || !$modelUsesHierarchy || !$templateShowsHierarchy || !$cssIndentsByDepth) {
    fwrite(STDERR, "Organizations list must order records as a parent/child tree, preserve cyclic/orphan records safely, paginate after tree ordering, and indent hierarchy levels by 1.5rem on desktop and 1rem on mobile.\n");
    exit(1);
}

echo "Organizations hierarchy list contract OK\n";
