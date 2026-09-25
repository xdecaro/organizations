<?php

namespace xdecaro\Component\Organizations\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use RuntimeException;
use xdecaro\Component\Organizations\Administrator\Service\CoreIntegrationService;
use xdecaro\Component\Organizations\Administrator\Service\DuplicateService;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationProviderService;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationBodiesService;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationDelegationsService;
use xdecaro\Component\Organizations\Administrator\Service\PeopleIntegrationService;
use xdecaro\Component\Organizations\Administrator\Service\PersonAppointmentsService;
use xdecaro\Component\Organizations\Administrator\Service\MembershipIntegrationService;
use xdecaro\Component\Organizations\Administrator\Service\AppointmentMembershipPolicyService;

final class OrganizationsComponent extends MVCComponent
{
    private ?CoreIntegrationService $core = null;
    private ?OrganizationProviderService $provider = null;
    private ?DuplicateService $duplicates = null;
    private ?PeopleIntegrationService $people = null;
    private ?PersonAppointmentsService $personAppointments = null;
    private ?OrganizationBodiesService $bodies = null;
    private ?OrganizationDelegationsService $delegations = null;
    private ?MembershipIntegrationService $membership = null;
    private ?AppointmentMembershipPolicyService $appointmentMembershipPolicy = null;

    public function setCoreIntegrationService(CoreIntegrationService $service): void
    {
        $this->core = $service;
    }

    public function getCoreIntegrationService(): CoreIntegrationService
    {
        return $this->core ??= new CoreIntegrationService();
    }

    public function setOrganizationProviderService(OrganizationProviderService $service): void
    {
        $this->provider = $service;
    }

    public function getOrganizationProviderService(): OrganizationProviderService
    {
        if (!$this->provider) {
            throw new RuntimeException('Organizations provider not initialized.');
        }

        return $this->provider;
    }

    public function setDuplicateService(DuplicateService $service): void
    {
        $this->duplicates = $service;
    }

    public function getDuplicateService(): DuplicateService
    {
        if (!$this->duplicates) {
            throw new RuntimeException('Duplicate service not initialized.');
        }

        return $this->duplicates;
    }

    public function setPeopleIntegrationService(PeopleIntegrationService $service): void
    {
        $this->people = $service;
    }

    public function getPeopleIntegrationService(): PeopleIntegrationService
    {
        if (!$this->people) {
            throw new RuntimeException('People integration service not initialized.');
        }

        return $this->people;
    }

    public function setPersonAppointmentsService(PersonAppointmentsService $service): void
    {
        $this->personAppointments = $service;
    }

    public function getPersonAppointmentsService(): PersonAppointmentsService
    {
        if (!$this->personAppointments) {
            throw new RuntimeException('Organizations person appointments service not initialized.');
        }

        return $this->personAppointments;
    }

    public function setOrganizationBodiesService(OrganizationBodiesService $service): void
    {
        $this->bodies = $service;
    }

    public function getOrganizationBodiesService(): OrganizationBodiesService
    {
        if (!$this->bodies) {
            throw new RuntimeException('Organizations bodies service not initialized.');
        }

        return $this->bodies;
    }

    public function setOrganizationDelegationsService(OrganizationDelegationsService $service): void
    {
        $this->delegations = $service;
    }

    public function getOrganizationDelegationsService(): OrganizationDelegationsService
    {
        if (!$this->delegations) {
            throw new RuntimeException('Organizations delegations service not initialized.');
        }

        return $this->delegations;
    }

    public function setMembershipIntegrationService(MembershipIntegrationService $service): void
    {
        $this->membership = $service;
    }

    public function getMembershipIntegrationService(): MembershipIntegrationService
    {
        if (!$this->membership) {
            throw new RuntimeException('Organizations Membership integration service not initialized.');
        }

        return $this->membership;
    }

    public function setAppointmentMembershipPolicyService(AppointmentMembershipPolicyService $service): void
    {
        $this->appointmentMembershipPolicy = $service;
    }

    public function getAppointmentMembershipPolicyService(): AppointmentMembershipPolicyService
    {
        if (!$this->appointmentMembershipPolicy) {
            throw new RuntimeException('Organizations appointment membership policy service not initialized.');
        }

        return $this->appointmentMembershipPolicy;
    }
}
