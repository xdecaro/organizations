<?php

declare(strict_types=1);

$view = __DIR__ . '/../component/admin/src/View/Delegations/HtmlView.php';
$template = __DIR__ . '/../component/admin/tmpl/delegations/default.php';
foreach ([$view, $template] as $path) { if (!is_file($path)) { fwrite(STDERR, "Missing delegations UI file\n"); exit(1); } }
$v = (string) file_get_contents($view); $t = (string) file_get_contents($template);
foreach (['core.manage','com_xdecaroorganizations.global-lists','OrganizationOptions'] as $needle) { if (!str_contains($v, $needle)) { fwrite(STDERR, "Delegations HtmlView missing: $needle\n"); exit(1); } }
foreach (['xdecaro-global-list-table','xdecaro-global-list-cards','filter_search','filter_organization','filter_visual_status','filter_temporal','expiring','30','activeTab=delegations','xdecaro-global-list-open'] as $needle) { if (!str_contains($t, $needle)) { fwrite(STDERR, "Delegations template missing: $needle\n"); exit(1); } }
foreach (['data-add','data-edit','data-delete','task=save','task=delete'] as $forbidden) { if (stripos($t, $forbidden) !== false) { fwrite(STDERR, "Delegations UI mutation marker: $forbidden\n"); exit(1); } }
fwrite(STDOUT, "global delegations UI contract: OK\n");
