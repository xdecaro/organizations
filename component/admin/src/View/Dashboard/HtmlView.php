<?php
namespace Xdecaro\Component\Decaroorganizations\Administrator\View\Dashboard;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Xdecaro\Component\Decaroorganizations\Administrator\Service\CoreIntegrationService;
final class HtmlView extends BaseHtmlView
{
    public bool $coreUiActive = false;
    public string $coreVersion = '';
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_decaroorganizations')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
        ToolbarHelper::title(Text::_('COM_DECAROORGANIZATIONS'), 'grid-2');
        $wa = $app->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_decaroorganizations');
        try { $core = Factory::getContainer()->get(CoreIntegrationService::class); $this->coreVersion = $core->getVersion(); $this->coreUiActive = $core->enableUi($wa); } catch (\Throwable) { $this->coreUiActive = false; }
        $wa->useStyle('com_decaroorganizations.admin');
        parent::display($tpl);
    }
}
