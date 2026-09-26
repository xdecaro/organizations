<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Version;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Throwable;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;
use xdecaro\Component\Organizations\Administrator\Service\CoreIntegrationService;

final class InformationModel extends BaseDatabaseModel
{
    public const UPDATE_SITE_URL = 'https://raw.githubusercontent.com/xdecaro/organizations/main/updates/pkg_organizations.xml';
    public const MINIMUM_JOOMLA = '6.0.0';
    public const MINIMUM_PHP = '8.1.0';

    private const EXPECTED_TABLES = [
        '#__xdecaroorganizations_organizations',
        '#__xdecaroorganizations_bodies',
        '#__xdecaroorganizations_appointments',
        '#__xdecaroorganizations_delegations',
        '#__xdecaroorganizations_affiliations',
    ];

    public function getDiagnostics(): array
    {
        $db = $this->getDatabase();
        $componentRuntime = Factory::getApplication()->bootComponent('com_xdecaroorganizations');
        $coreService = $componentRuntime instanceof OrganizationsComponent
            ? $componentRuntime->getCoreIntegrationService()
            : null;

        // Kept through Joomla's extension helper as the authoritative installed
        // administrator component record and for backward-compatible diagnostics.
        $record = ExtensionHelper::getExtensionRecord('com_xdecaroorganizations', 'component', 1);
        $manifest = new Registry($record->manifest_cache ?? '{}');
        $componentVersion = (string) $manifest->get('version', '');

        $componentExtension = $this->getExtension('component', 'com_xdecaroorganizations', null, 1);
        $packageExtension = $this->getExtension('package', 'pkg_organizations');
        $packageVersion = $this->getManifestVersion($packageExtension);
        if ($componentVersion === '') {
            $componentVersion = $this->getManifestVersion($componentExtension);
        }

        $installedVersion = $packageVersion !== '' ? $packageVersion : ($componentVersion !== '' ? $componentVersion : '0.0.0');
        $packageDetected = $packageExtension !== null && $packageVersion !== '';
        $installationConsistent = $packageDetected
            && $componentVersion !== ''
            && $packageVersion === $componentVersion;

        $tableHealth = $this->getTableHealth();
        $tablesPresent = !empty($tableHealth['all_present']);

        $joomlaVersion = (new Version())->getShortVersion();
        $phpVersion = PHP_VERSION;
        $environmentCompatible = version_compare($joomlaVersion, self::MINIMUM_JOOMLA, '>=')
            && version_compare($phpVersion, self::MINIMUM_PHP, '>=');

        $core = $this->getCoreDiagnostics($coreService);
        $integrations = $this->getIntegrations();
        $update = $this->getUpdateDiagnostics($packageExtension, $installedVersion);

        $criticalIssues = [];
        if (!$installationConsistent) {
            $criticalIssues[] = 'versions';
        }
        if (!$tablesPresent) {
            $criticalIssues[] = 'database';
        }
        if (!$environmentCompatible) {
            $criticalIssues[] = 'environment';
        }
        if (empty($core['installed']) || empty($core['compatible']) || empty($core['api_available'])) {
            $criticalIssues[] = 'core';
        }

        $warnings = [];
        if (empty($update['update_site_enabled'])) {
            $warnings[] = 'update_site';
        }
        if (!empty($update['update_available'])) {
            $warnings[] = 'update_available';
        }
        foreach ($integrations as $integration) {
            if (!empty($integration['installed']) && empty($integration['api_available'])) {
                $warnings[] = 'integration_' . (string) ($integration['key'] ?? 'unknown');
            }
        }

        return [
            'installed_version' => $installedVersion,
            'component_version' => (string) $manifest->get('version', ''),
            'package_version' => $packageVersion,
            'installation_consistent' => $installationConsistent,
            'package_detected' => $packageDetected,
            'component_id' => 'com_xdecaroorganizations',
            'package_id' => 'pkg_organizations',
            'repository' => 'xdecaro/organizations',
            'license' => 'GNU GPL v2 or later',
            'joomla_version' => $joomlaVersion,
            'minimum_joomla' => self::MINIMUM_JOOMLA,
            'php_version' => $phpVersion,
            'minimum_php' => self::MINIMUM_PHP,
            'database_type' => method_exists($db, 'getServerType') ? (string) $db->getServerType() : (string) $db->getName(),
            'database_version' => (string) $db->getVersion(),
            'environment_compatible' => $environmentCompatible,
            'table_health' => $tableHealth,
            'tables_present' => $tablesPresent,
            'table_ok' => $tablesPresent,
            'database_aligned' => $tablesPresent,
            'core' => $core,
            'core_version' => (string) ($core['version'] ?? ''),
            'core_ok' => !empty($core['compatible']) && !empty($core['api_available']),
            'integrations' => $integrations,
            'critical_issues' => $criticalIssues,
            'warnings' => array_values(array_unique($warnings)),
            'update_site_enabled' => (bool) ($update['update_site_enabled'] ?? false),
            'update_site_url' => self::UPDATE_SITE_URL,
            'last_check_timestamp' => (int) ($update['last_check_timestamp'] ?? 0),
            'latest_version' => (string) ($update['latest_version'] ?? ''),
            'update_available' => (bool) ($update['update_available'] ?? false),
            'update_state' => (string) ($update['update_state'] ?? 'inactive'),
        ];
    }

    private function getExtension(string $type, string $element, ?string $folder = null, ?int $clientId = null): ?object
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('extension_id'),
                $db->quoteName('manifest_cache'),
                $db->quoteName('enabled'),
                $db->quoteName('client_id'),
            ])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':type', $type)
            ->bind(':element', $element);

        if ($folder !== null) {
            $query->where($db->quoteName('folder') . ' = :folder')
                ->bind(':folder', $folder);
        }
        if ($clientId !== null) {
            $query->where($db->quoteName('client_id') . ' = :clientId')
                ->bind(':clientId', $clientId, ParameterType::INTEGER);
        }

        try {
            return $db->setQuery($query, 0, 1)->loadObject() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function getManifestVersion(?object $extension): string
    {
        if ($extension === null || empty($extension->manifest_cache)) {
            return '';
        }

        $manifest = json_decode((string) $extension->manifest_cache, true);

        return is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';
    }

    private function getTableHealth(): array
    {
        $db = $this->getDatabase();
        try {
            $available = array_flip($db->getTableList());
        } catch (Throwable) {
            $available = [];
        }

        $tables = [];
        foreach (self::EXPECTED_TABLES as $table) {
            $tables[$table] = isset($available[$db->replacePrefix($table)]);
        }

        $presentCount = count(array_filter($tables));

        return [
            'tables' => $tables,
            'present_count' => $presentCount,
            'expected_count' => count($tables),
            'all_present' => $presentCount === count($tables),
        ];
    }

    private function getCoreDiagnostics(?CoreIntegrationService $service): array
    {
        $canonicalPackage = $this->getExtension('package', 'pkg_core');
        $legacyPackage = $canonicalPackage === null ? $this->getExtension('package', 'pkg_xdecarocore') : null;
        $package = $canonicalPackage ?? $legacyPackage;
        $version = $service ? $service->getVersion() : '';
        if ($version === '') {
            $version = $this->getManifestVersion($package);
        }

        $installed = $package !== null || $version !== '';
        $apiAvailable = class_exists(\xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\xdecaro\Core\Integration\CapabilityRegistry::class);
        $compatible = $installed && $version !== ''
            && version_compare($version, CoreIntegrationService::MINIMUM_CORE, '>=');

        return [
            'name' => 'Core by xdecaro',
            'installed' => $installed,
            'version' => $version,
            'minimum_version' => CoreIntegrationService::MINIMUM_CORE,
            'compatible' => $compatible,
            'api_available' => $apiAvailable,
            'capabilities' => [
                'organizations.provider',
                'organizations.query',
                'organizations.hierarchy',
                'organizations.institutional_profile',
                'organizations.bodies',
                'organizations.delegations',
                'organizations.duplicates',
                'organizations.people_appointments',
                'organizations.appointment_membership_policy',
            ],
        ];
    }

    private function getIntegrations(): array
    {
        return [
            $this->getPeopleIntegration(),
            $this->getMembershipIntegration(),
        ];
    }

    private function getPeopleIntegration(): array
    {
        $extension = $this->getExtension('component', 'com_xdecaropeople');
        $apiAvailable = false;

        if ($extension !== null) {
            try {
                $component = Factory::getApplication()->bootComponent('com_xdecaropeople');
                $apiAvailable = is_object($component) && method_exists($component, 'getPersonProviderService');
            } catch (Throwable) {
                $apiAvailable = false;
            }
        }

        return [
            'key' => 'people',
            'name' => 'People by xdecaro',
            'element' => 'com_xdecaropeople',
            'installed' => $extension !== null,
            'version' => $this->getManifestVersion($extension),
            'api_available' => $apiAvailable,
            'required' => false,
        ];
    }

    private function getMembershipIntegration(): array
    {
        $extension = $this->getExtension('component', 'com_decaromembership');
        $apiAvailable = false;

        if ($extension !== null) {
            try {
                $component = Factory::getApplication()->bootComponent('com_decaromembership');
                $apiAvailable = is_object($component)
                    && (method_exists($component, 'getMembershipEligibilityService')
                        || method_exists($component, 'getMembershipPersonHistoryService'));
            } catch (Throwable) {
                $apiAvailable = false;
            }
        }

        return [
            'key' => 'membership',
            'name' => 'Membership by xdecaro',
            'element' => 'com_decaromembership',
            'installed' => $extension !== null,
            'version' => $this->getManifestVersion($extension),
            'api_available' => $apiAvailable,
            'required' => false,
        ];
    }

    private function getUpdateDiagnostics(?object $package, string $installedVersion): array
    {
        $db = $this->getDatabase();
        $update = null;
        $updateSite = null;

        if ($package !== null) {
            $extensionId = (int) ($package->extension_id ?? 0);
            if ($extensionId > 0) {
                try {
                    $query = $db->getQuery(true)
                        ->select([$db->quoteName('version'), $db->quoteName('detailsurl')])
                        ->from($db->quoteName('#__updates'))
                        ->where($db->quoteName('extension_id') . ' = :extensionId')
                        ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
                        ->order($db->quoteName('update_id') . ' DESC');
                    $update = $db->setQuery($query, 0, 1)->loadObject();

                    $location = self::UPDATE_SITE_URL;
                    $query = $db->getQuery(true)
                        ->select([
                            $db->quoteName('s.update_site_id'),
                            $db->quoteName('s.location'),
                            $db->quoteName('s.enabled'),
                            $db->quoteName('s.last_check_timestamp'),
                        ])
                        ->from($db->quoteName('#__update_sites', 's'))
                        ->innerJoin(
                            $db->quoteName('#__update_sites_extensions', 'm')
                            . ' ON ' . $db->quoteName('m.update_site_id') . ' = ' . $db->quoteName('s.update_site_id')
                        )
                        ->where($db->quoteName('m.extension_id') . ' = :extensionId')
                        ->where($db->quoteName('s.location') . ' = :location')
                        ->bind(':extensionId', $extensionId, ParameterType::INTEGER)
                        ->bind(':location', $location);
                    $updateSite = $db->setQuery($query, 0, 1)->loadObject();
                } catch (Throwable) {
                    $update = null;
                    $updateSite = null;
                }
            }
        }

        $latestVersion = $update !== null ? trim((string) ($update->version ?? '')) : '';
        $updateAvailable = $latestVersion !== ''
            && $installedVersion !== '0.0.0'
            && version_compare($latestVersion, $installedVersion, 'gt');
        $updateSiteEnabled = $updateSite !== null && (int) ($updateSite->enabled ?? 0) === 1;

        return [
            'update_site_enabled' => $updateSiteEnabled,
            'last_check_timestamp' => $updateSite !== null ? max(0, (int) ($updateSite->last_check_timestamp ?? 0)) : 0,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'update_state' => $updateAvailable ? 'available' : ($updateSiteEnabled ? 'current' : 'inactive'),
        ];
    }
}
