<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\WebAsset\WebAssetManager;
use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\CapabilityRegistry;
use xdecaro\Core\Integration\EntityReference;

final class CoreIntegrationService
{
    public const COMPONENT = 'com_xdecaroorganizations';
    public const MINIMUM_CORE = '1.4.0';

    public function getVersion(): string
    {
        return class_exists(\xdecaro\Core\Version::class)
            ? trim((string) \xdecaro\Core\Version::VERSION)
            : '';
    }

    public function enableUi(WebAssetManager $webAssetManager): bool
    {
        $version = $this->getVersion();
        if (
            $version === ''
            || version_compare($version, self::MINIMUM_CORE, '<')
            || !class_exists(\xdecaro\Core\Asset\AssetService::class)
        ) {
            return false;
        }

        try {
            return (new \xdecaro\Core\Asset\AssetService())->useComponents($webAssetManager);
        } catch (\Throwable) {
            return false;
        }
    }

    public function createEntityReference(int|string $id): EntityReference
    {
        if (!class_exists(EntityReference::class)) {
            throw new \RuntimeException('Core reference API unavailable.');
        }

        return new EntityReference(self::COMPONENT, 'organization', $id);
    }

    public function registerCapabilities(CapabilityRegistry $registry): void
    {
        $registry->registerMany([
            new Capability(self::COMPONENT, 'organizations.provider', '1.0.0'),
            new Capability(self::COMPONENT, 'organizations.query', '1.0.0'),
            new Capability(self::COMPONENT, 'organizations.hierarchy', '1.0.0'),
            new Capability(self::COMPONENT, 'organizations.institutional_profile', '1'),
            new Capability(self::COMPONENT, 'organizations.bodies', '1'),
            new Capability(self::COMPONENT, 'organizations.delegations', '1'),
            new Capability(self::COMPONENT, 'organizations.duplicates', '1.0.0'),
            new Capability(self::COMPONENT, 'organizations.people_appointments', '1'),
            new Capability(self::COMPONENT, 'organizations.appointment_membership_policy', '1'),
        ]);
    }
}
