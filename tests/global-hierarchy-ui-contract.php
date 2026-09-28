<?php

declare(strict_types=1);

$view = __DIR__ . '/../component/admin/src/View/Hierarchy/HtmlView.php';
$template = __DIR__ . '/../component/admin/tmpl/hierarchy/default.php';
foreach ([$view, $template] as $path) { if (!is_file($path)) { fwrite(STDERR, "Missing hierarchy UI file\n"); exit(1); } }
$v = (string) file_get_contents($view); $t = (string) file_get_contents($template);
foreach (['core.manage','com_xdecaroorganizations.global-lists','Diagnostics'] as $needle) { if (!str_contains($v, $needle)) { fwrite(STDERR, "Hierarchy HtmlView missing: $needle\n"); exit(1); } }
foreach (['xdecaro-global-list-table','xdecaro-global-list-cards','filter_search','filter_type','filter_structure','filter_operational','filter_roots_only','activeTab=hierarchy','xdecaro-hierarchy-depth','xdecaro-hierarchy-warning','xdecaro-global-list-open'] as $needle) { if (!str_contains($t, $needle)) { fwrite(STDERR, "Hierarchy template missing: $needle\n"); exit(1); } }
foreach (['data-add','data-edit','data-delete','task=save','task=delete'] as $forbidden) { if (stripos($t, $forbidden) !== false) { fwrite(STDERR, "Hierarchy UI mutation marker: $forbidden\n"); exit(1); } }
fwrite(STDOUT, "global hierarchy UI contract: OK\n");
