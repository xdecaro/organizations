<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;
use xdecaro\Component\Organizations\Administrator\Service\CoreIntegrationService;

final class InformationModel extends BaseDatabaseModel
{
    public function getDiagnostics(): array
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaroorganizations');
        $core = $component instanceof OrganizationsComponent ? $component->getCoreIntegrationService() : null;
        $db = $this->getDatabase();
        $table = $db->replacePrefix('#__xdecaroorganizations_organizations');

        $record = ExtensionHelper::getExtensionRecord('com_xdecaroorganizations', 'component', 1);
        $manifest = new Registry($record->manifest_cache ?? '{}');

        return [
            'component_version' => (string) $manifest->get('version', ''),
            'core_version' => $core ? $core->getVersion() : '',
            'core_ok' => $core ? version_compare($core->getVersion(), CoreIntegrationService::MINIMUM_CORE, '>=') : false,
            'table_ok' => in_array($table, $db->getTableList(), true),
            'php_version' => PHP_VERSION,
            'joomla_version' => JVERSION,
        ];
    }
}
