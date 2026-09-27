<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$servicePath = $root . '/component/admin/src/Service/MaintenanceLogService.php';
$viewPath = $root . '/component/admin/src/View/Maintenance/HtmlView.php';
$templatePath = $root . '/component/admin/tmpl/maintenance/default.php';
$cssPath = $root . '/component/media/css/maintenance.css';
$languageItPath = $root . '/component/admin/language/it-IT/com_xdecaroorganizations.maintenance.ini';
$languageEnPath = $root . '/component/admin/language/en-GB/com_xdecaroorganizations.maintenance.ini';

foreach ([$servicePath, $viewPath, $templatePath, $cssPath, $languageItPath, $languageEnPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing maintenance activity UI file: {$path}\n");
        exit(1);
    }
}

$service = (string) file_get_contents($servicePath);
$view = (string) file_get_contents($viewPath);
$template = (string) file_get_contents($templatePath);
$css = (string) file_get_contents($cssPath);
$languageIt = (string) file_get_contents($languageItPath);
$languageEn = (string) file_get_contents($languageEnPath);

foreach (['actor_name', '#__users', 'activity_action', 'activity_user'] as $needle) {
    if (!str_contains($service . $view, $needle)) {
        fwrite(STDERR, "Maintenance activity filtering/user enrichment missing: {$needle}\n");
        exit(1);
    }
}

foreach (['COM_XDECAROORGANIZATIONS_MAINT_SUMMARY', 'COM_XDECAROORGANIZATIONS_MAINT_STATUS', 'COM_XDECAROORGANIZATIONS_MAINT_DETAILS_TOGGLE', 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_BACKUP_CREATE', 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_DATABASE_REPAIR'] as $key) {
    if (!str_contains($template . $languageIt . $languageEn, $key)) {
        fwrite(STDERR, "Readable activity UI language contract missing: {$key}\n");
        exit(1);
    }
}

foreach (['xdecaro-activity-table', 'xdecaro-activity-summary', 'xdecaro-activity-status', '<details', 'data-label='] as $needle) {
    if (!str_contains($template, $needle)) {
        fwrite(STDERR, "Maintenance activity presentation missing: {$needle}\n");
        exit(1);
    }
}

if (str_contains($template, '<td><small class="text-body-secondary"><?php echo $this->escape(json_encode')) {
    fwrite(STDERR, "Raw maintenance JSON must not remain in the main table cell.\n");
    exit(1);
}

foreach (['.xdecaro-activity-table', '.xdecaro-activity-card', '@media (max-width: 767.98px)', 'white-space: normal'] as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "Maintenance activity responsive contract missing: {$needle}\n");
        exit(1);
    }
}

foreach (['activity_limit', 'activity_page', 'activity_action', 'activity_user'] as $needle) {
    if (!str_contains($view . $template, $needle)) {
        fwrite(STDERR, "Maintenance activity pagination/filter contract missing: {$needle}\n");
        exit(1);
    }
}

echo "Organizations 1.2.19 maintenance activity UI contract OK\n";
