<?php

namespace xdecaro\Component\Organizations\Administrator\View\Bodies;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public array $organizationOptions = [];
    public array $bodyTypeOptions = [];
    public $pagination;
    public $state;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_xdecaroorganizations')) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $app->getLanguage()->load('com_xdecaroorganizations.global', JPATH_ADMINISTRATOR . '/components/com_xdecaroorganizations', null, true);
        $this->items = $this->get('Items') ?: [];
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->organizationOptions = $this->get('OrganizationOptions') ?: [];
        $this->bodyTypeOptions = $this->get('BodyTypeOptions') ?: [];
        if ($errors = $this->get('Errors')) { throw new \RuntimeException(implode("\n", $errors)); }

        $app->getDocument()->getWebAssetManager()
            ->useStyle('com_xdecaroorganizations.admin')
            ->useStyle('com_xdecaroorganizations.global-lists')
            ->usePreset('choicesjs')
            ->useScript('webcomponent.field-fancy-select')
            ->useScript('com_xdecaroorganizations.global-lists');

        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_BODIES_TITLE'), 'sitemap');
        parent::display($tpl);
    }
}
