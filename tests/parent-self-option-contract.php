<?php

define('_JEXEC', 1);

$root = dirname(__DIR__);
$model = file_get_contents($root . '/component/admin/src/Model/OrganizationModel.php');
$form = file_get_contents($root . '/component/admin/forms/organization.xml');
$fieldPath = $root . '/component/admin/src/Field/OrganizationParentField.php';
$field = is_file($fieldPath) ? file_get_contents($fieldPath) : '';

require_once $root . '/component/admin/src/Service/OrganizationHierarchy.php';

use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

$items = [
    (object) ['id' => 1, 'parent_id' => 0, 'name' => 'ENS'],
    (object) ['id' => 2, 'parent_id' => 1, 'name' => 'Consiglio Regionale ENS Lazio'],
    (object) ['id' => 3, 'parent_id' => 2, 'name' => 'Sezione Provinciale ENS Roma'],
    (object) ['id' => 4, 'parent_id' => 1, 'name' => 'Consiglio Regionale ENS Campania'],
];

$descendantIds = method_exists(OrganizationHierarchy::class, 'descendantIds')
    ? OrganizationHierarchy::descendantIds($items, 2)
    : [];
sort($descendantIds);

$usesHierarchicalParentField = str_contains($form, 'name="parent_id" type="OrganizationParent"');
$fieldUsesHierarchy = str_contains($field, 'OrganizationHierarchy::order')
    && str_contains($field, 'OrganizationHierarchy::descendantIds')
    && str_contains($field, 'hierarchy_depth')
    && str_contains($field, '↳')
    && str_contains($field, "\u{00A0}");
$excludesCurrentRecord = str_contains($field, '$currentId')
    && (str_contains($field, '$item->id') || str_contains($field, '$item->id'));

if (!$usesHierarchicalParentField || !$fieldUsesHierarchy || !$excludesCurrentRecord) {
    fwrite(STDERR, "Parent organization choices must use a dedicated hierarchical field with progressive labels.\n");
    exit(1);
}

if ($descendantIds !== [3]) {
    fwrite(STDERR, "Parent organization choices must exclude all descendants of the record being edited.\n");
    exit(1);
}

if (!str_contains($model, 'validParent')) {
    fwrite(STDERR, "Backend hierarchy validation must remain enabled as a second safety layer.\n");
    exit(1);
}

echo "Organizations hierarchical parent option contract OK\n";
