<?php

namespace xdecaro\Component\Organizations\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;

final class HtmlView extends BaseHtmlView
{
    public array $dashboard = [];
    public int $total = 0;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_xdecaroorganizations')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $app->getLanguage()->load(
            'com_xdecaroorganizations.dashboard',
            JPATH_ADMINISTRATOR . '/components/com_xdecaroorganizations',
            null,
            true
        );

        $this->dashboard = (array) $this->get('Dashboard');
        $this->total = (int) ($this->dashboard['counts']['total'] ?? 0);

        $component = $app->bootComponent('com_xdecaroorganizations');
        if ($component instanceof OrganizationsComponent) {
            $component->getCoreIntegrationService()->enableUi($this->document->getWebAssetManager());
        }
        $this->document->getWebAssetManager()->useStyle('com_xdecaroorganizations.admin');

        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_DASHBOARD'), 'building');
        parent::display($tpl);
    }
}
