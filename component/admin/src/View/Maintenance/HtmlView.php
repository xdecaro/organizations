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
    public array $storage = [];
    public bool $canBackup = false;
    public bool $canRestore = false;
    public bool $canRepair = false;
    public bool $canDestructive = false;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
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
        catch (\Throwable $e) { $this->schema = ['ok' => false, 'status' => 'Errore', 'error' => $e->getMessage()]; }
        try { $this->backups = $component->getBackupService()->list(); }
        catch (\Throwable) { $this->backups = []; }
        try { $this->activity = $component->getMaintenanceLogService()->recent(30); }
        catch (\Throwable) { $this->activity = []; }
        $this->storage = $component->getBackupStorageService()->isHealthy();

        $wa = $this->document->getWebAssetManager();
        $component->getCoreIntegrationService()->enableUi($wa);
        $wa->useStyle('com_xdecaroorganizations.admin');
        $wa->useStyle('com_xdecaroorganizations.maintenance');
        $wa->useScript('com_xdecaroorganizations.database-maintenance');

        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_MAINTENANCE'), 'database');
        parent::display($tpl);
    }
}
