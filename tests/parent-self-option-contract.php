<?php
$root = dirname(__DIR__);
$model = file_get_contents($root . '/component/admin/src/Model/OrganizationModel.php');

$hasCurrentIdLookup = str_contains($model, "getInt('id')") || str_contains($model, 'getInt("id")');
$hasDynamicParentQuery = str_contains($model, "setFieldAttribute('parent_id'") || str_contains($model, 'setFieldAttribute("parent_id"');
$excludesCurrentId = str_contains($model, 'id <>') || str_contains($model, "' <> '") || str_contains($model, '" <> "');

if (!$hasCurrentIdLookup || !$hasDynamicParentQuery || !$excludesCurrentId) {
    fwrite(STDERR, "Editing an organization must remove the current record from the parent organization choices.\n");
    exit(1);
}

if (!str_contains($model, 'validParent')) {
    fwrite(STDERR, "Backend hierarchy validation must remain enabled as a second safety layer.\n");
    exit(1);
}

echo "Organizations parent self-option contract OK\n";
