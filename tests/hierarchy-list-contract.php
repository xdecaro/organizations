<?php
$root = dirname(__DIR__);
$model = file_get_contents($root . '/component/admin/src/Model/OrganizationsModel.php');
$template = file_get_contents($root . '/component/admin/tmpl/organizations/default.php');
$css = file_get_contents($root . '/component/media/css/admin.css');

$modelBuildsTree = str_contains($model, 'public function getItems()')
    && str_contains($model, 'hierarchy_depth')
    && str_contains($model, 'parent_id')
    && str_contains($model, 'strnatcasecmp')
    && str_contains($model, 'array_slice');

$templateShowsHierarchy = str_contains($template, 'hierarchy_depth')
    && str_contains($template, 'xdecaro-organization-tree-name')
    && str_contains($template, '--xdecaro-org-depth')
    && str_contains($template, '↳');

$cssIndentsByDepth = str_contains($css, '.xdecaro-organization-tree-name')
    && str_contains($css, 'var(--xdecaro-org-depth')
    && str_contains($css, 'padding-inline-start');

if (!$modelBuildsTree || !$templateShowsHierarchy || !$cssIndentsByDepth) {
    fwrite(STDERR, "Organizations list must order records as a parent/child tree and visibly indent each name by hierarchy depth.\n");
    exit(1);
}

echo "Organizations hierarchy list contract OK\n";
