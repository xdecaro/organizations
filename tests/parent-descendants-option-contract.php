<?php
$root = dirname(__DIR__);
$model = file_get_contents($root . '/component/admin/src/Model/OrganizationModel.php');

$hasDescendantTraversal = str_contains($model, 'descendant') || str_contains($model, 'Descendant');
$readsChildren = str_contains($model, "where($db->quoteName('parent_id')") || str_contains($model, 'parent_id');
$excludesMultipleIds = str_contains($model, 'NOT IN') || str_contains($model, 'notIn');
$keepsBackendGuard = str_contains($model, 'validParent');

if (!$hasDescendantTraversal || !$readsChildren || !$excludesMultipleIds) {
    fwrite(STDERR, "Parent choices must exclude the current organization and all of its descendants.\n");
    exit(1);
}

if (!$keepsBackendGuard) {
    fwrite(STDERR, "Backend hierarchy validation must remain enabled as a second safety layer.\n");
    exit(1);
}

echo "Organizations descendant parent-option contract OK\n";
