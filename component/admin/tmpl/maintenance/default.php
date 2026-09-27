<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$schema = (array) $this->schema;
$backups = (array) $this->backups;
$activity = (array) $this->activity;
$activityFilters = (array) $this->activityFilters;
$activityFilterOptions = (array) $this->activityFilterOptions;
$activityTotal = (int) $this->activityTotal;
$activityLimit = (int) $this->activityLimit;
$activityPage = (int) $this->activityPage;
$schemaOk = !empty($schema['ok']);
$token = Session::getFormToken();

$formatBytes = static function (int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
};

$actionLabels = [
    'backup_create' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_BACKUP_CREATE',
    'backup_download' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_BACKUP_DOWNLOAD',
    'backup_delete' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_BACKUP_DELETE',
    'restore_preview' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_RESTORE_PREVIEW',
    'restore_full' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_RESTORE_FULL',
    'database_check' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_DATABASE_CHECK',
    'database_repair' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_DATABASE_REPAIR',
    'database_empty' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_DATABASE_EMPTY',
    'database_recreate' => 'COM_XDECAROORGANIZATIONS_MAINT_ACTION_DATABASE_RECREATE',
];

$activityActionLabel = static function (string $action) use ($actionLabels): string {
    if (isset($actionLabels[$action])) return Text::_($actionLabels[$action]);
    $fallback = trim(str_replace('_', ' ', $action));
    return $fallback !== '' ? ucfirst($fallback) : Text::_('COM_XDECAROORGANIZATIONS_MAINT_ACTION_UNKNOWN');
};

$formatCounts = static function (array $counts): string {
    $map = [
        '#__xdecaroorganizations_organizations' => 'COM_XDECAROORGANIZATIONS_ORGANIZATIONS',
        '#__xdecaroorganizations_bodies' => 'COM_XDECAROORGANIZATIONS_FIELDSET_BODIES',
        '#__xdecaroorganizations_appointments' => 'COM_XDECAROORGANIZATIONS_MEMBERS',
        '#__xdecaroorganizations_delegations' => 'COM_XDECAROORGANIZATIONS_FIELDSET_DELEGATIONS',
        '#__xdecaroorganizations_affiliations' => 'COM_XDECAROORGANIZATIONS_FIELDSET_AFFILIATIONS',
    ];
    $parts = [];
    foreach ($map as $table => $label) {
        if (array_key_exists($table, $counts)) $parts[] = Text::_($label) . ' ' . (int) $counts[$table];
    }
    return implode(' · ', $parts);
};

$activityStatus = static function (string $action, array $metadata): array {
    if (!empty($metadata['error'])) {
        return ['label' => Text::_('COM_XDECAROORGANIZATIONS_MAINT_STATUS_ERROR'), 'class' => 'is-danger'];
    }
    if (($action === 'database_check' && array_key_exists('ok', $metadata) && empty($metadata['ok']))
        || ($action === 'database_repair' && array_key_exists('after_ok', $metadata) && empty($metadata['after_ok']))
        || ($action === 'restore_preview' && array_key_exists('compatible', $metadata) && empty($metadata['compatible']))
        || ($action === 'database_empty' && array_key_exists('schema_ok', $metadata) && empty($metadata['schema_ok']))
        || ($action === 'database_recreate' && array_key_exists('schema_ok', $metadata) && empty($metadata['schema_ok']))) {
        return ['label' => Text::_('COM_XDECAROORGANIZATIONS_MAINT_STATUS_WARNING'), 'class' => 'is-warning'];
    }
    return ['label' => Text::_('COM_XDECAROORGANIZATIONS_MAINT_STATUS_OK'), 'class' => 'is-success'];
};

$activitySummary = static function (string $action, array $metadata, ?string $subjectUuid = null) use ($formatBytes, $formatCounts): string {
    $parts = [];
    switch ($action) {
        case 'backup_create':
            $counts = $formatCounts((array) ($metadata['counts'] ?? []));
            if ($counts !== '') $parts[] = $counts;
            if (isset($metadata['size_bytes'])) $parts[] = $formatBytes((int) $metadata['size_bytes']);
            break;
        case 'backup_download':
            if (isset($metadata['size_bytes'])) $parts[] = $formatBytes((int) $metadata['size_bytes']);
            if (!empty($metadata['filename'])) $parts[] = (string) $metadata['filename'];
            break;
        case 'backup_delete':
            if (!empty($metadata['filename'])) $parts[] = (string) $metadata['filename'];
            break;
        case 'restore_preview':
            if (!empty($metadata['component_version'])) $parts[] = 'v' . (string) $metadata['component_version'];
            $counts = $formatCounts((array) ($metadata['counts'] ?? []));
            if ($counts !== '') $parts[] = $counts;
            break;
        case 'restore_full':
            $counts = $formatCounts((array) ($metadata['restored_counts'] ?? []));
            if ($counts !== '') $parts[] = $counts;
            if (!empty($metadata['safety_backup_filename'])) $parts[] = Text::_('COM_XDECAROORGANIZATIONS_MAINT_SAFETY_BACKUP') . ': ' . (string) $metadata['safety_backup_filename'];
            break;
        case 'database_check':
            $parts[] = !empty($metadata['ok'])
                ? Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_COMPLIANT')
                : Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_REVIEW');
            break;
        case 'database_repair':
            $parts[] = Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATIONS_SUMMARY', count((array) ($metadata['operations'] ?? [])));
            $parts[] = !empty($metadata['after_ok'])
                ? Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_COMPLIANT')
                : Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_REVIEW');
            break;
        case 'database_empty':
            $counts = $formatCounts((array) ($metadata['removed'] ?? []));
            if ($counts !== '') $parts[] = $counts;
            if (!empty($metadata['safety_backup_filename'])) $parts[] = Text::_('COM_XDECAROORGANIZATIONS_MAINT_SAFETY_BACKUP') . ': ' . (string) $metadata['safety_backup_filename'];
            break;
        case 'database_recreate':
            $parts[] = Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_RECREATED');
            if (!empty($metadata['safety_backup_filename'])) $parts[] = Text::_('COM_XDECAROORGANIZATIONS_MAINT_SAFETY_BACKUP') . ': ' . (string) $metadata['safety_backup_filename'];
            break;
    }
    if ($parts === [] && $subjectUuid !== null && trim($subjectUuid) !== '') $parts[] = $subjectUuid;
    return $parts !== [] ? implode(' · ', $parts) : Text::_('COM_XDECAROORGANIZATIONS_MAINT_NO_SUMMARY');
};

$buildActivityUrl = static function (int $page) use ($activityFilters, $activityLimit): string {
    $query = [
        'option' => 'com_xdecaroorganizations',
        'view' => 'maintenance',
        'activity_page' => max(1, $page),
        'activity_limit' => $activityLimit,
    ];
    if (!empty($activityFilters['activity_action'])) $query['activity_action'] = (string) $activityFilters['activity_action'];
    if (!empty($activityFilters['activity_user'])) $query['activity_user'] = (int) $activityFilters['activity_user'];
    return Route::_('index.php?' . http_build_query($query));
};

$differenceGroups = [
    'missing_tables' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_MISSING_TABLES',
    'unexpected_tables' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_UNEXPECTED_TABLES',
    'missing_columns' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_MISSING_COLUMNS',
    'incompatible_columns' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_INCOMPATIBLE_COLUMNS',
    'unknown_columns' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_UNKNOWN_COLUMNS',
    'missing_indexes' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_MISSING_INDEXES',
    'incompatible_indexes' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_INCOMPATIBLE_INDEXES',
    'unknown_indexes' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_UNKNOWN_INDEXES',
    'engine_differences' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_ENGINE',
    'collation_differences' => 'COM_XDECAROORGANIZATIONS_MAINT_DIFF_COLLATION',
];
?>
<div class="xdecaro-scope xdecaro-maintenance-page">
    <div class="mb-4">
        <h2 class="h4 mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINTENANCE'); ?></h2>
        <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_DESC'); ?></p>
    </div>

    <div class="xdecaro-maintenance-grid mb-4">
        <section class="card">
            <div class="card-body">
                <div class="xdecaro-card-heading mb-3">
                    <div>
                        <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_SAFETY'); ?></span>
                        <h3 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_BACKUP'); ?></h3>
                    </div>
                    <span class="xdecaro-status-badge <?php echo !empty($this->storage['ok']) ? 'is-success' : 'is-danger'; ?>">
                        <?php echo $this->escape((string) ($this->storage['message'] ?? '')); ?>
                    </span>
                </div>
                <p><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_BACKUP_DESC'); ?></p>
                <?php if ($this->canBackup) : ?>
                    <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.createBackup'); ?>" method="post">
                        <button type="submit" class="btn btn-primary"><span class="icon-archive" aria-hidden="true"></span> <?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_CREATE_BACKUP'); ?></button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </form>
                <?php else : ?>
                    <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_NO_BACKUP_PERMISSION'); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-body">
                <div class="xdecaro-card-heading mb-3">
                    <div>
                        <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_CONTROLLED'); ?></span>
                        <h3 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE'); ?></h3>
                    </div>
                </div>
                <p><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_DESC'); ?></p>
                <?php if ($this->canRestore) : ?>
                    <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations'); ?>" method="post" enctype="multipart/form-data" class="d-grid gap-2">
                        <label class="form-label" for="organizations-restore-file"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_FILE'); ?></label>
                        <input id="organizations-restore-file" class="form-control" type="file" name="restore_file" accept=".zip,application/zip" required>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="task" value="maintenance.previewRestore" class="btn btn-outline-primary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_VERIFY_FILE'); ?></button>
                            <button type="submit" name="task" value="maintenance.restoreFull" class="btn btn-warning" data-maintenance-confirm="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_CONFIRM_TEXT')); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_FULL'); ?></button>
                        </div>
                        <input type="hidden" name="confirm_restore" value="1">
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="card mb-4">
        <div class="card-body">
            <div class="xdecaro-card-heading mb-3">
                <div>
                    <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_CANONICAL_SCHEMA'); ?></span>
                    <h3 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_DATABASE'); ?></h3>
                </div>
                <span class="xdecaro-status-badge <?php echo $schemaOk ? 'is-success' : 'is-warning'; ?>"><?php echo $this->escape((string) ($schema['status'] ?? ($schemaOk ? 'OK' : Text::_('COM_XDECAROORGANIZATIONS_INFO_WARNING')))); ?></span>
            </div>
            <p><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_DATABASE_DESC'); ?></p>

            <div class="alert <?php echo $schemaOk ? 'alert-success' : 'alert-warning'; ?>" role="status">
                <strong><?php echo $schemaOk ? Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_OK') : Text::_('COM_XDECAROORGANIZATIONS_MAINT_DIFFERENCES'); ?></strong>
                <div><?php echo $schemaOk ? Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_OK_DESC') : Text::_('COM_XDECAROORGANIZATIONS_MAINT_SCHEMA_DIFF_DESC'); ?></div>
            </div>

            <?php if (!$schemaOk) : ?>
                <div class="accordion mb-3" id="organizations-schema-differences">
                    <?php foreach ($differenceGroups as $key => $label) : ?>
                        <?php $items = (array) ($schema[$key] ?? []); if ($items === []) continue; ?>
                        <div class="accordion-item">
                            <h4 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#org-diff-<?php echo $this->escape($key); ?>"><?php echo Text::_($label); ?> <span class="badge bg-secondary ms-2"><?php echo count($items); ?></span></button></h4>
                            <div id="org-diff-<?php echo $this->escape($key); ?>" class="accordion-collapse collapse" data-bs-parent="#organizations-schema-differences"><div class="accordion-body"><pre class="mb-0 small"><?php echo $this->escape(json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></pre></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="xdecaro-maintenance-actions">
                <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.checkDatabase'); ?>" method="post" class="card">
                    <div class="card-body d-grid gap-2">
                        <h4 class="h6 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_CHECK_DATABASE'); ?></h4>
                        <p class="text-body-secondary mb-2"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_CHECK_DATABASE_DESC'); ?></p>
                        <button type="submit" class="btn btn-outline-primary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_CHECK_DATABASE'); ?></button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </div>
                </form>

                <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.repairDatabase'); ?>" method="post" class="card">
                    <div class="card-body d-grid gap-2">
                        <h4 class="h6 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_REPAIR_DATABASE'); ?></h4>
                        <p class="text-body-secondary mb-2"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_REPAIR_DATABASE_DESC'); ?></p>
                        <button type="submit" class="btn btn-warning" <?php echo $this->canRepair ? '' : 'disabled'; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_REPAIR_DATABASE'); ?></button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </div>
                </form>

                <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.emptyDatabase'); ?>" method="post" class="card border-danger-subtle" data-typed-confirm="SVUOTA">
                    <div class="card-body d-grid gap-2">
                        <h4 class="h6 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_EMPTY'); ?></h4>
                        <p class="text-body-secondary mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_EMPTY_DESC'); ?></p>
                        <label class="form-label mb-0" for="organizations-empty-confirm"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_TYPE_TO_CONFIRM', 'SVUOTA'); ?></label>
                        <input id="organizations-empty-confirm" class="form-control" type="text" name="database_confirmation" autocomplete="off" spellcheck="false" <?php echo $this->canDestructive ? '' : 'disabled'; ?>>
                        <button type="submit" class="btn btn-danger" data-typed-submit disabled><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_EMPTY'); ?></button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </div>
                </form>

                <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.recreateDatabase'); ?>" method="post" class="card border-danger" data-typed-confirm="RICREA">
                    <div class="card-body d-grid gap-2">
                        <h4 class="h6 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RECREATE'); ?></h4>
                        <p class="text-body-secondary mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RECREATE_DESC'); ?></p>
                        <label class="form-label mb-0" for="organizations-recreate-confirm"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_TYPE_TO_CONFIRM', 'RICREA'); ?></label>
                        <input id="organizations-recreate-confirm" class="form-control" type="text" name="database_confirmation" autocomplete="off" spellcheck="false" <?php echo $this->canDestructive ? '' : 'disabled'; ?>>
                        <button type="submit" class="btn btn-danger" data-typed-submit disabled><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RECREATE'); ?></button>
                        <?php echo HTMLHelper::_('form.token'); ?>
                    </div>
                </form>
            </div>

            <div class="alert alert-warning mt-3 mb-0" role="note">
                <strong><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_EXTERNAL_WARNING_TITLE'); ?></strong>
                <?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_EXTERNAL_WARNING'); ?> Membership, Competitions, Documents.
            </div>
        </div>
    </section>

    <section class="card mb-4">
        <div class="card-body">
            <div class="xdecaro-card-heading mb-3"><div><span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_ARCHIVE'); ?></span><h3 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_AVAILABLE_BACKUPS'); ?></h3></div><span class="xdecaro-status-badge is-muted"><?php echo count($backups); ?></span></div>
            <?php if ($backups === []) : ?>
                <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_NO_BACKUPS'); ?></p>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table align-middle xdecaro-maintenance-table">
                        <thead><tr>
                            <th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_ORGANIZATIONS'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_BODIES'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_DELEGATIONS'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_AFFILIATIONS'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_INFO_VERSION'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_SIZE'); ?></th><th>SHA256</th><th class="text-end"><?php echo Text::_('JACTIONS'); ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($backups as $backup) : ?>
                            <?php $uuid = (string) ($backup['uuid'] ?? ''); ?>
                            <tr>
                                <td class="text-nowrap"><?php echo $this->escape((string) ($backup['created'] ?? '')); ?></td>
                                <td><?php echo (int) ($backup['organizations_count'] ?? 0); ?></td><td><?php echo (int) ($backup['bodies_count'] ?? 0); ?></td><td><?php echo (int) ($backup['appointments_count'] ?? 0); ?></td><td><?php echo (int) ($backup['delegations_count'] ?? 0); ?></td><td><?php echo (int) ($backup['affiliations_count'] ?? 0); ?></td>
                                <td><?php echo $this->escape((string) ($backup['component_version'] ?? '')); ?></td>
                                <td class="text-nowrap"><?php echo $formatBytes((int) ($backup['size_bytes'] ?? 0)); ?></td>
                                <td><code title="<?php echo $this->escape((string) ($backup['sha256'] ?? '')); ?>"><?php echo $this->escape(substr((string) ($backup['sha256'] ?? ''), 0, 12)); ?>…</code></td>
                                <td><div class="d-flex flex-wrap justify-content-end gap-1">
                                    <?php if ($this->canBackup) : ?>
                                        <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.verifyBackup'); ?>" method="post"><input type="hidden" name="backup_uuid" value="<?php echo $this->escape($uuid); ?>"><button class="btn btn-sm btn-outline-primary" type="submit"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_VERIFY'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.downloadBackup&backup_uuid=' . rawurlencode($uuid) . '&' . $token . '=1'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_DOWNLOAD'); ?></a>
                                    <?php endif; ?>
                                    <?php if ($this->canRestore) : ?>
                                        <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.restoreFull'); ?>" method="post"><input type="hidden" name="backup_uuid" value="<?php echo $this->escape($uuid); ?>"><input type="hidden" name="confirm_restore" value="1"><button class="btn btn-sm btn-warning" type="submit" data-maintenance-confirm="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_CONFIRM_TEXT')); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
                                    <?php endif; ?>
                                    <?php if ($this->canBackup) : ?>
                                        <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=maintenance.deleteBackup'); ?>" method="post"><input type="hidden" name="backup_uuid" value="<?php echo $this->escape($uuid); ?>"><input type="hidden" name="confirm_delete_backup" value="1"><button class="btn btn-sm btn-outline-danger" type="submit" data-maintenance-confirm="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_DELETE_CONFIRM_TEXT')); ?>"><?php echo Text::_('JACTION_DELETE'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form>
                                    <?php endif; ?>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-body">
            <div class="xdecaro-card-heading mb-3">
                <div>
                    <span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_AUDIT'); ?></span>
                    <h3 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_ACTIVITY'); ?></h3>
                </div>
                <span class="xdecaro-status-badge is-muted"><?php echo $activityTotal; ?></span>
            </div>

            <form action="<?php echo Route::_('index.php'); ?>" method="get" class="xdecaro-activity-filters mb-3">
                <input type="hidden" name="option" value="com_xdecaroorganizations">
                <input type="hidden" name="view" value="maintenance">
                <input type="hidden" name="activity_page" value="1">
                <div>
                    <label class="form-label" for="activity-action"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_FILTER_ACTION'); ?></label>
                    <select class="form-select" id="activity-action" name="activity_action">
                        <option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_FILTER_ALL_ACTIONS'); ?></option>
                        <?php foreach ((array) ($activityFilterOptions['actions'] ?? []) as $actionOption) : ?>
                            <option value="<?php echo $this->escape((string) $actionOption); ?>" <?php echo ((string) ($activityFilters['activity_action'] ?? '') === (string) $actionOption) ? 'selected' : ''; ?>><?php echo $this->escape($activityActionLabel((string) $actionOption)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="activity-user"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_FILTER_USER'); ?></label>
                    <select class="form-select" id="activity-user" name="activity_user">
                        <option value="0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_FILTER_ALL_USERS'); ?></option>
                        <?php foreach ((array) ($activityFilterOptions['users'] ?? []) as $userOption) : ?>
                            <?php
                            $userId = (int) ($userOption['id'] ?? 0);
                            $userName = trim((string) ($userOption['name'] ?? ''));
                            if ($userName === '') $userName = trim((string) ($userOption['username'] ?? ''));
                            $userLabel = $userName !== '' ? $userName . ' (#' . $userId . ')' : '#' . $userId;
                            ?>
                            <option value="<?php echo $userId; ?>" <?php echo ((int) ($activityFilters['activity_user'] ?? 0) === $userId) ? 'selected' : ''; ?>><?php echo $this->escape($userLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="activity-limit"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_FILTER_LIMIT'); ?></label>
                    <select class="form-select" id="activity-limit" name="activity_limit">
                        <?php foreach ([10, 20, 50] as $limitOption) : ?>
                            <option value="<?php echo $limitOption; ?>" <?php echo $activityLimit === $limitOption ? 'selected' : ''; ?>><?php echo $limitOption; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xdecaro-activity-filter-actions">
                    <button class="btn btn-primary" type="submit"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_FILTER_APPLY'); ?></button>
                    <a class="btn btn-outline-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=maintenance'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_FILTER_RESET'); ?></a>
                </div>
            </form>

            <?php if ($activity === []) : ?>
                <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_NO_ACTIVITY'); ?></p>
            <?php else : ?>
                <div class="table-responsive xdecaro-activity-card">
                    <table class="table align-middle mb-0 xdecaro-activity-table">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('JDATE'); ?></th>
                                <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_ACTION'); ?></th>
                                <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_USER'); ?></th>
                                <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_SUMMARY'); ?></th>
                                <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_STATUS'); ?></th>
                                <th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_DETAILS'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity as $row) : ?>
                                <?php
                                $action = (string) ($row['action'] ?? '');
                                $metadata = (array) ($row['metadata'] ?? []);
                                $status = $activityStatus($action, $metadata);
                                $actorId = (int) ($row['actor_user_id'] ?? 0);
                                $actorName = trim((string) ($row['actor_name'] ?? ''));
                                if ($actorName === '') $actorName = trim((string) ($row['actor_username'] ?? ''));
                                $summary = $activitySummary($action, $metadata, isset($row['subject_uuid']) ? (string) $row['subject_uuid'] : null);
                                $metadataJson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                if (!is_string($metadataJson)) $metadataJson = '{}';
                                ?>
                                <tr>
                                    <td class="xdecaro-activity-date" data-label="<?php echo $this->escape(Text::_('JDATE')); ?>"><?php echo HTMLHelper::_('date', (string) ($row['created'] ?? ''), 'd/m/Y H:i', true); ?></td>
                                    <td class="xdecaro-activity-action" data-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_ACTION')); ?>"><strong><?php echo $this->escape($activityActionLabel($action)); ?></strong></td>
                                    <td class="xdecaro-activity-user" data-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_USER')); ?>">
                                        <?php if ($actorName !== '') : ?><span><?php echo $this->escape($actorName); ?></span><small class="text-body-secondary d-block">#<?php echo $actorId; ?></small><?php else : ?>#<?php echo $actorId; ?><?php endif; ?>
                                    </td>
                                    <td class="xdecaro-activity-summary" data-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_SUMMARY')); ?>"><?php echo $this->escape($summary); ?></td>
                                    <td class="xdecaro-activity-status" data-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_STATUS')); ?>"><span class="xdecaro-status-badge <?php echo $this->escape((string) $status['class']); ?>"><?php echo $this->escape((string) $status['label']); ?></span></td>
                                    <td class="xdecaro-activity-details-cell text-end" data-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_DETAILS')); ?>">
                                        <details class="xdecaro-activity-details">
                                            <summary class="btn btn-sm btn-outline-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_DETAILS_TOGGLE'); ?></summary>
                                            <div class="xdecaro-activity-details-panel"><pre class="mb-0 small"><?php echo $this->escape($metadataJson); ?></pre></div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php $activityPages = max(1, (int) ceil($activityTotal / max(1, $activityLimit))); ?>
                <?php if ($activityPages > 1) : ?>
                    <nav class="xdecaro-activity-pagination mt-3" aria-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_MAINT_ACTIVITY_PAGINATION')); ?>">
                        <a class="btn btn-sm btn-outline-secondary <?php echo $activityPage <= 1 ? 'disabled' : ''; ?>" <?php echo $activityPage <= 1 ? 'aria-disabled="true" tabindex="-1"' : 'href="' . $this->escape($buildActivityUrl($activityPage - 1)) . '"'; ?>><?php echo Text::_('JPREV'); ?></a>
                        <span class="text-body-secondary"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_PAGE_INFO', $activityPage, $activityPages, $activityTotal); ?></span>
                        <a class="btn btn-sm btn-outline-secondary <?php echo $activityPage >= $activityPages ? 'disabled' : ''; ?>" <?php echo $activityPage >= $activityPages ? 'aria-disabled="true" tabindex="-1"' : 'href="' . $this->escape($buildActivityUrl($activityPage + 1)) . '"'; ?>><?php echo Text::_('JNEXT'); ?></a>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
