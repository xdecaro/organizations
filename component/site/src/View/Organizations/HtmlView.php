<?php

namespace xdecaro\Component\Organizations\Site\View\Organizations;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;
    public $state;
    public $params;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->items = $this->get('Items') ?: [];
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->params = $app->getParams();

        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_xdecaroorganizations.site');

        $title = trim((string) $this->params->get('page_title', ''));
        $this->document->setTitle($title !== '' ? $title : Text::_('COM_XDECAROORGANIZATIONS_SITE_ORGANIZATIONS'));

        parent::display($tpl);
    }
}
