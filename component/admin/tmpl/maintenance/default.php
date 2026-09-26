<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$schema = (array) $this->schema;
$backups = (array) $this->backups;
$activity = (array) $this->activity;
$schemaOk = !empty($schema['ok']);
$token = Session::getFormToken();

$formatBytes = static function (int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
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
            <div class="xdecaro-card-heading mb-3"><div><span class="xdecaro-eyebrow"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_AUDIT'); ?></span><h3 class="h5 mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_ACTIVITY'); ?></h3></div></div>
            <?php if ($activity === []) : ?><p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_NO_ACTIVITY'); ?></p>
            <?php else : ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th><?php echo Text::_('JDATE'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_ACTION'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_USER'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_MAINT_DETAILS'); ?></th></tr></thead><tbody>
                <?php foreach ($activity as $row) : ?><tr><td class="text-nowrap"><?php echo $this->escape((string) ($row['created'] ?? '')); ?></td><td><code><?php echo $this->escape((string) ($row['action'] ?? '')); ?></code></td><td>#<?php echo (int) ($row['actor_user_id'] ?? 0); ?></td><td><small class="text-body-secondary"><?php echo $this->escape(json_encode((array) ($row['metadata'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></small></td></tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </div>
    </section>
</div>
