<?php

namespace xdecaro\Component\Organizations\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use RuntimeException;
use xdecaro\Component\Organizations\Administrator\Service\AppointmentMembershipPolicyService;
use xdecaro\Component\Organizations\Administrator\Service\BackupService;
use xdecaro\Component\Organizations\Administrator\Service\BackupStorageService;
use xdecaro\Component\Organizations\Administrator\Service\CoreIntegrationService;
use xdecaro\Component\Organizations\Administrator\Service\DatabaseMaintenanceService;
use xdecaro\Component\Organizations\Administrator\Service\DatabaseSchemaDefinition;
use xdecaro\Component\Organizations\Administrator\Service\DatabaseSchemaInspector;
use xdecaro\Component\Organizations\Administrator\Service\DuplicateService;
use xdecaro\Component\Organizations\Administrator\Service\MaintenanceLogService;
use xdecaro\Component\Organizations\Administrator\Service\MembershipIntegrationService;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationBodiesService;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationDelegationsService;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationProviderService;
use xdecaro\Component\Organizations\Administrator\Service\PeopleIntegrationService;
use xdecaro\Component\Organizations\Administrator\Service\PersonAppointmentsService;
use xdecaro\Component\Organizations\Administrator\Service\RestoreService;

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
    private ?DatabaseSchemaDefinition $databaseSchemaDefinition = null;
    private ?DatabaseSchemaInspector $databaseSchemaInspector = null;
    private ?DatabaseMaintenanceService $databaseMaintenance = null;
    private ?BackupStorageService $backupStorage = null;
    private ?MaintenanceLogService $maintenanceLog = null;
    private ?BackupService $backup = null;
    private ?RestoreService $restore = null;

    public function setCoreIntegrationService(CoreIntegrationService $service): void { $this->core = $service; }
    public function getCoreIntegrationService(): CoreIntegrationService { return $this->core ??= new CoreIntegrationService(); }

    public function setOrganizationProviderService(OrganizationProviderService $service): void { $this->provider = $service; }
    public function getOrganizationProviderService(): OrganizationProviderService
    {
        if (!$this->provider) throw new RuntimeException('Organizations provider not initialized.');
        return $this->provider;
    }

    public function setDuplicateService(DuplicateService $service): void { $this->duplicates = $service; }
    public function getDuplicateService(): DuplicateService
    {
        if (!$this->duplicates) throw new RuntimeException('Duplicate service not initialized.');
        return $this->duplicates;
    }

    public function setPeopleIntegrationService(PeopleIntegrationService $service): void { $this->people = $service; }
    public function getPeopleIntegrationService(): PeopleIntegrationService
    {
        if (!$this->people) throw new RuntimeException('People integration service not initialized.');
        return $this->people;
    }

    public function setPersonAppointmentsService(PersonAppointmentsService $service): void { $this->personAppointments = $service; }
    public function getPersonAppointmentsService(): PersonAppointmentsService
    {
        if (!$this->personAppointments) throw new RuntimeException('Organizations person appointments service not initialized.');
        return $this->personAppointments;
    }

    public function setOrganizationBodiesService(OrganizationBodiesService $service): void { $this->bodies = $service; }
    public function getOrganizationBodiesService(): OrganizationBodiesService
    {
        if (!$this->bodies) throw new RuntimeException('Organizations bodies service not initialized.');
        return $this->bodies;
    }

    public function setOrganizationDelegationsService(OrganizationDelegationsService $service): void { $this->delegations = $service; }
    public function getOrganizationDelegationsService(): OrganizationDelegationsService
    {
        if (!$this->delegations) throw new RuntimeException('Organizations delegations service not initialized.');
        return $this->delegations;
    }

    public function setMembershipIntegrationService(MembershipIntegrationService $service): void { $this->membership = $service; }
    public function getMembershipIntegrationService(): MembershipIntegrationService
    {
        if (!$this->membership) throw new RuntimeException('Organizations Membership integration service not initialized.');
        return $this->membership;
    }

    public function setAppointmentMembershipPolicyService(AppointmentMembershipPolicyService $service): void { $this->appointmentMembershipPolicy = $service; }
    public function getAppointmentMembershipPolicyService(): AppointmentMembershipPolicyService
    {
        if (!$this->appointmentMembershipPolicy) throw new RuntimeException('Organizations appointment membership policy service not initialized.');
        return $this->appointmentMembershipPolicy;
    }

    public function setDatabaseSchemaDefinition(DatabaseSchemaDefinition $service): void { $this->databaseSchemaDefinition = $service; }
    public function getDatabaseSchemaDefinition(): DatabaseSchemaDefinition
    {
        if (!$this->databaseSchemaDefinition) throw new RuntimeException('Organizations database schema definition not initialized.');
        return $this->databaseSchemaDefinition;
    }

    public function setDatabaseSchemaInspector(DatabaseSchemaInspector $service): void { $this->databaseSchemaInspector = $service; }
    public function getDatabaseSchemaInspector(): DatabaseSchemaInspector
    {
        if (!$this->databaseSchemaInspector) throw new RuntimeException('Organizations database schema inspector not initialized.');
        return $this->databaseSchemaInspector;
    }

    public function setDatabaseMaintenanceService(DatabaseMaintenanceService $service): void { $this->databaseMaintenance = $service; }
    public function getDatabaseMaintenanceService(): DatabaseMaintenanceService
    {
        if (!$this->databaseMaintenance) throw new RuntimeException('Organizations database maintenance service not initialized.');
        return $this->databaseMaintenance;
    }

    public function setBackupStorageService(BackupStorageService $service): void { $this->backupStorage = $service; }
    public function getBackupStorageService(): BackupStorageService
    {
        if (!$this->backupStorage) throw new RuntimeException('Organizations backup storage service not initialized.');
        return $this->backupStorage;
    }

    public function setMaintenanceLogService(MaintenanceLogService $service): void { $this->maintenanceLog = $service; }
    public function getMaintenanceLogService(): MaintenanceLogService
    {
        if (!$this->maintenanceLog) throw new RuntimeException('Organizations maintenance log service not initialized.');
        return $this->maintenanceLog;
    }

    public function setBackupService(BackupService $service): void { $this->backup = $service; }
    public function getBackupService(): BackupService
    {
        if (!$this->backup) throw new RuntimeException('Organizations backup service not initialized.');
        return $this->backup;
    }

    public function setRestoreService(RestoreService $service): void { $this->restore = $service; }
    public function getRestoreService(): RestoreService
    {
        if (!$this->restore) throw new RuntimeException('Organizations restore service not initialized.');
        return $this->restore;
    }
}
