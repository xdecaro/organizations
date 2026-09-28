<?php

namespace xdecaro\Component\Organizations\Administrator\View\Affiliations;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;
    public $state;
    public array $sourceOptions = [];
    public array $targetOptions = [];

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
        $this->sourceOptions = $this->get('SourceOrganizationOptions') ?: [];
        $this->targetOptions = $this->get('TargetOrganizationOptions') ?: [];

        if ($errors = $this->get('Errors')) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        $app->getDocument()->getWebAssetManager()
            ->useStyle('com_xdecaroorganizations.admin')
            ->useStyle('com_xdecaroorganizations.global-lists')
            ->usePreset('choicesjs')
            ->useScript('webcomponent.field-fancy-select')
            ->useScript('com_xdecaroorganizations.global-lists-behavior');

        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_AFFILIATIONS_TITLE'), 'link');

        parent::display($tpl);
    }
}
