<?php

namespace xdecaro\Component\Organizations\Administrator\View\Organization;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Throwable;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;
use xdecaro\Component\Organizations\Administrator\Model\OrganizationAppointmentsModel;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public array $appointments = [];
    public bool $peopleAvailable = false;
    public bool $canCreateAppointments = false;
    public bool $canEditAppointments = false;
    public bool $canDeleteAppointments = false;

    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $new = empty($this->item->id);

        if (!$user->authorise($new ? 'core.create' : 'core.edit', 'com_xdecaroorganizations')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $component = $app->bootComponent('com_xdecaroorganizations');
        if ($component instanceof OrganizationsComponent) {
            $component->getCoreIntegrationService()->enableUi($this->document->getWebAssetManager());
            $this->loadAppointments($component);
        }

        $this->canCreateAppointments = $user->authorise('core.create', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canEditAppointments = $user->authorise('core.edit', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canDeleteAppointments = $user->authorise('core.delete', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');

        $wa = $this->document->getWebAssetManager();
        $wa->useStyle('com_xdecaroorganizations.admin');
        $wa->useScript('com_xdecaroorganizations.organization-edit');
        $wa->useScript('bootstrap.modal');

        ToolbarHelper::title(
            $new ? Text::_('COM_XDECAROORGANIZATIONS_ORGANIZATION_NEW') : Text::_('COM_XDECAROORGANIZATIONS_ORGANIZATION_EDIT'),
            'building'
        );
        ToolbarHelper::apply('organization.apply');
        ToolbarHelper::save('organization.save');
        ToolbarHelper::save2new('organization.save2new');
        ToolbarHelper::cancel('organization.cancel');

        parent::display($tpl);
    }

    private function loadAppointments(OrganizationsComponent $component): void
    {
        try {
            $this->peopleAvailable = $component->getPeopleIntegrationService()->isAvailable();
        } catch (Throwable) {
            $this->peopleAvailable = false;
        }

        $organizationId = (int) ($this->item->id ?? 0);
        if ($organizationId < 1) {
            return;
        }

        try {
            $model = $component->getMVCFactory()->createModel(
                'OrganizationAppointments',
                'Administrator',
                ['ignore_request' => true]
            );

            if (!$model instanceof OrganizationAppointmentsModel) {
                throw new \RuntimeException('Unable to create OrganizationAppointments model.');
            }

            $model->setOrganizationId($organizationId);
            $items = $model->getItems();
            if ($items === false) {
                throw new \RuntimeException((string) ($model->getError() ?: 'Unable to load organization appointments.'));
            }

            $this->appointments = $items;
        } catch (Throwable $exception) {
            $this->appointments = [];
            Log::add(
                'Organizations appointments load failed: ' . $exception->getMessage(),
                Log::ERROR,
                'com_xdecaroorganizations'
            );
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
        }
    }
}
