<?php

namespace xdecaro\Component\Organizations\Administrator\View\Hierarchy;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public array $diagnostics = [];
    public array $typeOptions = [];
    public array $structureOptions = [];
    public array $operationalOptions = [];
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
        $this->diagnostics = $this->get('Diagnostics') ?: [];
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->typeOptions = $this->get('TypeOptions') ?: [];
        $this->structureOptions = $this->get('StructureOptions') ?: [];
        $this->operationalOptions = $this->get('OperationalOptions') ?: [];
        if ($errors = $this->get('Errors')) { throw new \RuntimeException(implode("\n", $errors)); }
        $app->getDocument()->getWebAssetManager()
            ->useStyle('com_xdecaroorganizations.admin')
            ->useStyle('com_xdecaroorganizations.global-lists')
            ->useScript('com_xdecaroorganizations.global-lists-behavior');
        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_HIERARCHY_TITLE'), 'sitemap');
        parent::display($tpl);
    }
}
