<?php

namespace xdecaro\Component\Organizations\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use RuntimeException;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;

final class MaintenanceController extends BaseController
{
    protected $option = 'com_xdecaroorganizations';

    public function createBackup(): void
    {
        $this->checkToken(); $this->requirePermission('organizations.backup');
        try {
            $backup = $this->component()->getBackupService()->create((int) $this->app->getIdentity()->id, 'manual');
            $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_BACKUP_CREATED', (string) ($backup['filename'] ?? '')), 'success');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function verifyBackup(): void
    {
        $this->checkToken(); $this->requirePermission('organizations.backup');
        try {
            $uuid = strtolower(trim($this->input->getString('backup_uuid')));
            $row = $this->component()->getBackupService()->verify($uuid);
            $preview = $this->component()->getRestoreService()->preview((string) ($row['path'] ?? ''), (int) $this->app->getIdentity()->id);
            $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_BACKUP_VERIFIED', (int) ($preview['counts']['#__xdecaroorganizations_organizations'] ?? 0)), 'success');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function downloadBackup(): void
    {
        $this->checkToken('get'); $this->requirePermission('organizations.backup');
        $uuid = strtolower(trim($this->input->getString('backup_uuid')));
        $row = $this->component()->getBackupService()->resolveDownload($uuid, (int) $this->app->getIdentity()->id);
        $path = (string) ($row['path'] ?? '');
        if ($path === '' || !is_file($path)) throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_BACKUP_UNAVAILABLE'), 404);
        $name = basename((string) ($row['filename'] ?? basename($path)));
        $this->app->setHeader('Content-Type', 'application/zip', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="' . addslashes($name) . '"', true);
        $this->app->setHeader('Content-Length', (string) filesize($path), true);
        $this->app->sendHeaders(); readfile($path); $this->app->close();
    }

    public function deleteBackup(): void
    {
        $this->checkToken(); $this->requirePermission('organizations.backup');
        if (!$this->input->getBool('confirm_delete_backup')) throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_DELETE_CONFIRM_REQUIRED'), 400);
        try {
            $this->component()->getBackupService()->delete(strtolower(trim($this->input->getString('backup_uuid'))), (int) $this->app->getIdentity()->id);
            $this->redirectMaintenance(Text::_('COM_XDECAROORGANIZATIONS_MAINT_BACKUP_DELETED'), 'success');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function previewRestore(): void
    {
        $this->checkToken(); $this->requirePermission('organizations.restore');
        try {
            $preview = $this->component()->getRestoreService()->preview($this->restorePath(), (int) $this->app->getIdentity()->id);
            $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_PREVIEW_OK', (int) ($preview['counts']['#__xdecaroorganizations_organizations'] ?? 0)), 'success');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function restoreFull(): void
    {
        $this->checkToken(); $this->requirePermission('organizations.restore');
        if (!$this->input->getBool('confirm_restore')) throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_CONFIRM_REQUIRED'), 400);
        try {
            $result = $this->component()->getRestoreService()->restoreFull($this->restorePath(), (int) $this->app->getIdentity()->id);
            $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_RESTORE_DONE', (string) ($result['safety_backup_filename'] ?? '')), 'success');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function checkDatabase(): void
    {
        $this->checkToken(); $this->requirePermission('core.manage');
        try {
            $result = $this->component()->getDatabaseMaintenanceService()->check((int) $this->app->getIdentity()->id, true);
            $this->redirectMaintenance(Text::_(!empty($result['ok']) ? 'COM_XDECAROORGANIZATIONS_MAINT_CHECK_OK' : 'COM_XDECAROORGANIZATIONS_MAINT_CHECK_DIFFERENCES'), !empty($result['ok']) ? 'success' : 'warning');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function repairDatabase(): void
    {
        $this->checkToken(); $this->requirePermission('core.manage'); $this->requirePermission('organizations.database_repair');
        try {
            $result = $this->component()->getDatabaseMaintenanceService()->repair((int) $this->app->getIdentity()->id);
            $ok = !empty($result['after']['ok']);
            $this->redirectMaintenance(Text::sprintf($ok ? 'COM_XDECAROORGANIZATIONS_MAINT_REPAIR_DONE' : 'COM_XDECAROORGANIZATIONS_MAINT_REPAIR_DIFFERENCES', count((array) ($result['operations'] ?? []))), $ok ? 'success' : 'warning');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function emptyDatabase(): void
    {
        $this->checkToken(); $this->requirePermission('core.manage'); $this->requirePermission('organizations.database_destructive');
        $confirmation = $this->input->getString('database_confirmation');
        if ($confirmation !== 'SVUOTA') { $this->redirectMaintenance(Text::_('COM_XDECAROORGANIZATIONS_MAINT_EMPTY_CONFIRM_ERROR'), 'error'); return; }
        try {
            $result = $this->component()->getDatabaseMaintenanceService()->emptyFunctionalData((int) $this->app->getIdentity()->id, $confirmation);
            $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_EMPTY_DONE', (string) ($result['safety_backup_filename'] ?? '')), 'success');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    public function recreateDatabase(): void
    {
        $this->checkToken(); $this->requirePermission('core.manage'); $this->requirePermission('organizations.database_destructive');
        $confirmation = $this->input->getString('database_confirmation');
        if ($confirmation !== 'RICREA') { $this->redirectMaintenance(Text::_('COM_XDECAROORGANIZATIONS_MAINT_RECREATE_CONFIRM_ERROR'), 'error'); return; }
        try {
            $result = $this->component()->getDatabaseMaintenanceService()->recreateFunctionalDatabase((int) $this->app->getIdentity()->id, $confirmation);
            $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_RECREATE_DONE', (string) ($result['safety_backup_filename'] ?? '')), 'success');
        } catch (\Throwable $e) { $this->redirectMaintenance(Text::sprintf('COM_XDECAROORGANIZATIONS_MAINT_OPERATION_ERROR', $e->getMessage()), 'error'); }
    }

    private function restorePath(): string
    {
        $backupUuid = strtolower(trim($this->input->getString('backup_uuid')));
        if ($backupUuid !== '') return (string) ($this->component()->getBackupService()->verify($backupUuid)['path'] ?? '');
        $upload = $this->input->files->get('restore_file', null, 'raw');
        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_SELECT_BACKUP'), 400);
        $path = (string) ($upload['tmp_name'] ?? ''); $name = (string) ($upload['name'] ?? '');
        if ($path === '' || !is_uploaded_file($path) || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_SELECT_BACKUP'), 400);
        $mime = class_exists(\finfo::class) ? (new \finfo(FILEINFO_MIME_TYPE))->file($path) : null;
        if (is_string($mime) && !in_array($mime, ['application/zip','application/x-zip-compressed','application/octet-stream'], true)) throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_INVALID_BACKUP_MIME'), 400);
        return $path;
    }

    private function requirePermission(string $action): void
    {
        $this->app->getLanguage()->load(
            'com_xdecaroorganizations.maintenance',
            JPATH_ADMINISTRATOR . '/components/com_xdecaroorganizations',
            null,
            true
        );
        if (!$this->app->getIdentity()->authorise($action, 'com_xdecaroorganizations')) throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    private function component(): OrganizationsComponent
    {
        $component = $this->app->bootComponent('com_xdecaroorganizations');
        if (!$component instanceof OrganizationsComponent) throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_SERVICE_UNAVAILABLE'));
        return $component;
    }

    private function redirectMaintenance(string $message, string $type = 'message'): void
    {
        $this->app->enqueueMessage($message, $type); $this->setRedirect('index.php?option=com_xdecaroorganizations&view=maintenance');
    }
}
