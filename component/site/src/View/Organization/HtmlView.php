<?php

namespace xdecaro\Component\Organizations\Site\View\Organization;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use RuntimeException;

final class HtmlView extends BaseHtmlView
{
    public ?object $item = null;
    public array $hierarchyPath = [];
    public array $children = [];
    public array $bodies = [];
    public array $appointments = [];
    public array $delegations = [];
    public $params;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $model = $this->getModel();

        $this->item = $model->getItem();
        if (!$this->item) {
            throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_SITE_NOT_FOUND'), 404);
        }

        $this->hierarchyPath = $model->getHierarchyPath();
        $this->children = $model->getChildren();
        $this->bodies = $model->getBodies();
        $this->appointments = $model->getAppointments();
        $this->delegations = $model->getDelegations();
        $this->params = $app->getParams();

        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_xdecaroorganizations.site');

        $pageTitle = trim((string) $this->params->get('page_title', ''));
        $this->document->setTitle($pageTitle !== '' ? $pageTitle : (string) $this->item->name);

        parent::display($tpl);
    }

    public function organizationUrl(int $id): string
    {
        $itemId = Factory::getApplication()->getInput()->getInt('Itemid', 0);
        $itemIdQuery = $itemId > 0 ? '&Itemid=' . $itemId : '';

        return Route::_('index.php?option=com_xdecaroorganizations&view=organization&id=' . $id . $itemIdQuery);
    }
}
