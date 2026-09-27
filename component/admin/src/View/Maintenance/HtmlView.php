<?php

namespace xdecaro\Component\Organizations\Administrator\View\Maintenance;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;

final class HtmlView extends BaseHtmlView
{
    public array $schema = [];
    public array $backups = [];
    public array $activity = [];
    public array $activityFilters = ['activity_action' => '', 'activity_user' => 0];
    public array $activityFilterOptions = ['actions' => [], 'users' => []];
    public int $activityTotal = 0;
    public int $activityLimit = 20;
    public int $activityPage = 1;
    public array $storage = [];
    public bool $backupReady = false;
    public bool $canBackup = false;
    public bool $canRestore = false;
    public bool $canRepair = false;
    public bool $canDestructive = false;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $app->getLanguage()->load(
            'com_xdecaroorganizations.maintenance',
            JPATH_ADMINISTRATOR . '/components/com_xdecaroorganizations',
            null,
            true
        );
        $identity = $app->getIdentity();
        if (!$identity->authorise('core.manage', 'com_xdecaroorganizations')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $component = $app->bootComponent('com_xdecaroorganizations');
        if (!$component instanceof OrganizationsComponent) {
            throw new \RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MAINT_SERVICE_UNAVAILABLE'));
        }

        $this->canBackup = $identity->authorise('organizations.backup', 'com_xdecaroorganizations');
        $this->canRestore = $identity->authorise('organizations.restore', 'com_xdecaroorganizations');
        $this->canRepair = $identity->authorise('organizations.database_repair', 'com_xdecaroorganizations');
        $this->canDestructive = $identity->authorise('organizations.database_destructive', 'com_xdecaroorganizations');

        try { $this->schema = $component->getDatabaseMaintenanceService()->inspect(); }
        catch (\Throwable $e) { $this->schema = ['ok' => false, 'status' => Text::_('JERROR_ERROR'), 'error' => $e->getMessage()]; }
        try { $this->backups = $component->getBackupService()->list(); }
        catch (\Throwable) { $this->backups = []; }

        $allowedLimits = [10, 20, 50];
        $requestedLimit = $app->input->getInt('activity_limit', 20);
        $this->activityLimit = in_array($requestedLimit, $allowedLimits, true) ? $requestedLimit : 20;
        $this->activityFilters = [
            'activity_action' => substr(trim($app->input->getCmd('activity_action', '')), 0, 64),
            'activity_user' => max(0, $app->input->getInt('activity_user', 0)),
        ];
        $this->activityPage = max(1, $app->input->getInt('activity_page', 1));

        try {
            $activityService = $component->getMaintenanceLogService();
            $this->activityTotal = $activityService->countFiltered($this->activityFilters);
            $totalPages = max(1, (int) ceil($this->activityTotal / $this->activityLimit));
            $this->activityPage = min($this->activityPage, $totalPages);
            $offset = ($this->activityPage - 1) * $this->activityLimit;
            $this->activity = $activityService->recent($this->activityLimit, $offset, $this->activityFilters);
            $this->activityFilterOptions = $activityService->filterOptions();
        } catch (\Throwable) {
            $this->activity = [];
            $this->activityTotal = 0;
            $this->activityPage = 1;
            $this->activityFilterOptions = ['actions' => [], 'users' => []];
        }

        $this->storage = $component->getBackupStorageService()->isHealthy();
        $schemaTables = (array) ($this->schema['tables'] ?? []);
        $maintenanceTablesReady = !empty($schemaTables['#__xdecaroorganizations_backups'])
            && !empty($schemaTables['#__xdecaroorganizations_maintenance_log']);
        $this->backupReady = !empty($this->storage['ok']) && $maintenanceTablesReady;

        if (!empty($this->storage['ok']) && !$maintenanceTablesReady) {
            $this->storage['ok'] = false;
            $this->storage['message'] = Text::_('COM_XDECAROORGANIZATIONS_MAINT_DIFFERENCES');
        }

        $wa = $this->document->getWebAssetManager();
        $component->getCoreIntegrationService()->enableUi($wa);
        $wa->useStyle('com_xdecaroorganizations.admin');
        $wa->useStyle('com_xdecaroorganizations.maintenance');
        $wa->useScript('com_xdecaroorganizations.database-maintenance');

        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_MAINTENANCE'), 'database');
        parent::display($tpl);
    }
}
