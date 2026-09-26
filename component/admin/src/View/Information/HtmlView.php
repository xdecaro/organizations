<?php

namespace xdecaro\Component\Organizations\Administrator\View\Information;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;

final class HtmlView extends BaseHtmlView
{
    public array $diagnostics = [];
    public bool $canManageInstaller = false;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $identity = $app->getIdentity();

        if (!$identity->authorise('core.manage', 'com_xdecaroorganizations')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $app->getLanguage()->load(
            'com_xdecaroorganizations.dashboard',
            JPATH_ADMINISTRATOR . '/components/com_xdecaroorganizations',
            null,
            true
        );

        $this->diagnostics = (array) $this->get('Diagnostics');
        $this->canManageInstaller = $identity->authorise('core.manage', 'com_installer');

        $component = $app->bootComponent('com_xdecaroorganizations');
        if ($component instanceof OrganizationsComponent) {
            $component->getCoreIntegrationService()->enableUi($this->document->getWebAssetManager());
        }
        $this->document->getWebAssetManager()->useStyle('com_xdecaroorganizations.admin');

        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_INFORMATION'), 'info-circle');
        parent::display($tpl);
    }
}
