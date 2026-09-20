<?php

namespace xdecaro\Component\Organizations\Administrator\View\Organization;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Throwable;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;
use xdecaro\Component\Organizations\Administrator\Model\OrganizationAppointmentsModel;
use xdecaro\Component\Organizations\Administrator\Model\OrganizationBodiesModel;
use xdecaro\Component\Organizations\Administrator\Model\OrganizationDelegationsModel;
use xdecaro\Component\Organizations\Administrator\Model\OrganizationModel;

final class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public array $appointments = [];
    public array $bodies = [];
    public array $delegations = [];
    public array $hierarchyPath = [];
    public array $hierarchyDescendants = [];
    public string $appointmentMembershipRequirement = 'none';
    public bool $peopleAvailable = false;
    public bool $canCreateAppointments = false;
    public bool $canEditAppointments = false;
    public bool $canDeleteAppointments = false;
    public bool $canCreateBodies = false;
    public bool $canEditBodies = false;
    public bool $canCreateDelegations = false;
    public bool $canEditDelegations = false;
    public string $auditCreatedByName = '';
    public string $auditModifiedByName = '';

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

        $this->loadSystemAudit();
        $this->loadHierarchyContext();

        $component = $app->bootComponent('com_xdecaroorganizations');
        if ($component instanceof OrganizationsComponent) {
            $component->getCoreIntegrationService()->enableUi($this->document->getWebAssetManager());
            $this->loadAppointments($component);
            $this->loadBodies($component);
            $this->loadDelegations($component);
        }

        $this->canCreateAppointments = $user->authorise('core.create', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canEditAppointments = $user->authorise('core.edit', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canDeleteAppointments = $user->authorise('core.delete', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canCreateBodies = $user->authorise('core.create', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canEditBodies = $user->authorise('core.edit', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canCreateDelegations = $user->authorise('core.create', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations');
        $this->canEditDelegations = $user->authorise('core.edit', 'com_xdecaroorganizations')
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

    private function loadSystemAudit(): void
    {
        $this->auditCreatedByName = $this->auditUserName((int) ($this->item->created_by ?? 0));
        $this->auditModifiedByName = $this->auditUserName((int) ($this->item->modified_by ?? 0));
    }

    private function auditUserName(int $userId): string
    {
        if ($userId < 1) {
            return '';
        }

        try {
            $factory = Factory::getContainer()->get(UserFactoryInterface::class);
            $user = $factory->loadUserById($userId);
            $name = trim((string) ($user->name ?? ''));

            return $name !== '' ? $name : trim((string) ($user->username ?? ''));
        } catch (Throwable $exception) {
            Log::add(
                'Organizations audit user lookup failed for user ' . $userId . ': ' . $exception->getMessage(),
                Log::WARNING,
                'com_xdecaroorganizations'
            );

            return '';
        }
    }

    private function loadHierarchyContext(): void
    {
        $organizationId = (int) ($this->item->id ?? 0);
        if ($organizationId < 1) {
            return;
        }

        try {
            $model = $this->getModel();

            if (!$model instanceof OrganizationModel) {
                throw new \RuntimeException('Unable to resolve Organization model.');
            }

            $context = $model->getHierarchyContext($organizationId);
            $this->hierarchyPath = array_values((array) ($context['path'] ?? []));
            $this->hierarchyDescendants = array_values((array) ($context['descendants'] ?? []));
        } catch (Throwable $exception) {
            $this->hierarchyPath = [];
            $this->hierarchyDescendants = [];
            Log::add(
                'Organizations hierarchy context load failed: ' . $exception->getMessage(),
                Log::ERROR,
                'com_xdecaroorganizations'
            );
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
        }
    }

    private function loadDelegations(OrganizationsComponent $component): void
    {
        $organizationId = (int) ($this->item->id ?? 0);
        if ($organizationId < 1) {
            return;
        }

        try {
            $model = $component->getMVCFactory()->createModel(
                'OrganizationDelegations',
                'Administrator',
                ['ignore_request' => true]
            );

            if (!$model instanceof OrganizationDelegationsModel) {
                throw new \RuntimeException('Unable to create OrganizationDelegations model.');
            }

            $model->setOrganizationId($organizationId);
            $items = $model->getItems();
            if ($items === false) {
                throw new \RuntimeException((string) ($model->getError() ?: 'Unable to load organization delegations.'));
            }

            $this->delegations = $items;
        } catch (Throwable $exception) {
            $this->delegations = [];
            Log::add(
                'Organizations delegations load failed: ' . $exception->getMessage(),
                Log::ERROR,
                'com_xdecaroorganizations'
            );
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
        }
    }

    private function loadBodies(OrganizationsComponent $component): void
    {
        $organizationId = (int) ($this->item->id ?? 0);
        if ($organizationId < 1) {
            return;
        }

        try {
            $model = $component->getMVCFactory()->createModel(
                'OrganizationBodies',
                'Administrator',
                ['ignore_request' => true]
            );

            if (!$model instanceof OrganizationBodiesModel) {
                throw new \RuntimeException('Unable to create OrganizationBodies model.');
            }

            $model->setOrganizationId($organizationId);
            $items = $model->getItems();
            if ($items === false) {
                throw new \RuntimeException((string) ($model->getError() ?: 'Unable to load organization bodies.'));
            }

            $this->bodies = $items;
        } catch (Throwable $exception) {
            $this->bodies = [];
            Log::add(
                'Organizations bodies load failed: ' . $exception->getMessage(),
                Log::ERROR,
                'com_xdecaroorganizations'
            );
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
        }
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
            $policy = $component->getAppointmentMembershipPolicyService();
            $this->appointmentMembershipRequirement = $policy->resolveRequirement($organizationId);

            if ($this->appointmentMembershipRequirement !== 'none') {
                foreach ($this->appointments as $appointment) {
                    $status = (string) ($appointment->visual_status ?? '');
                    if (!in_array($status, ['active', 'scheduled'], true)) {
                        continue;
                    }

                    $appointment->membership_eligibility = $policy->evaluate(
                        $organizationId,
                        (string) ($appointment->person_uuid ?? '')
                    );
                }
            }
        } catch (Throwable $exception) {
            $this->appointments = [];
            $this->appointmentMembershipRequirement = 'none';
            Log::add(
                'Organizations appointments load failed: ' . $exception->getMessage(),
                Log::ERROR,
                'com_xdecaroorganizations'
            );
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
        }
    }
}
