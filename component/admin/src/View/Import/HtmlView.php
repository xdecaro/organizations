<?php

namespace xdecaro\Component\Organizations\Administrator\View\Import;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use RuntimeException;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;

final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $admin = $user->authorise('core.admin', 'com_xdecaroorganizations');

        if (!$admin && (
            !$user->authorise('core.create', 'com_xdecaroorganizations')
            || !$user->authorise('organizations.view_sensitive', 'com_xdecaroorganizations')
        )) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $component = $app->bootComponent('com_xdecaroorganizations');
        if ($component instanceof OrganizationsComponent) {
            $component->getCoreIntegrationService()->enableUi($this->document->getWebAssetManager());
        }

        $wam = $this->document->getWebAssetManager();
        $wam->useStyle('com_xdecaroorganizations.admin');
        $wam->useScript('com_xdecaroorganizations.import');

        $this->document->addScriptOptions('com_xdecaroorganizations.import', [
            'analyzeUrl' => Route::_('index.php?option=com_xdecaroorganizations&task=import.analyze&format=json', false),
            'batchUrl' => Route::_('index.php?option=com_xdecaroorganizations&task=import.batch&format=json', false),
            'token' => Session::getFormToken(),
            'batchSize' => 100,
            'strings' => [
                'chooseFile' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CHOOSE_FILE'),
                'fileError' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_FILE_ERROR'),
                'rows' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ROWS'),
                'valid' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_VALID'),
                'duplicates' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_FILE_DUPLICATES'),
                'invalid' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_INVALID'),
                'existing' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_EXISTING'),
                'newOrganizations' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_NEW'),
                'analyzing' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ANALYZING'),
                'ready' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_READY'),
                'importing' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_IMPORTING'),
                'complete' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_COMPLETE'),
                'requestError' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REQUEST_ERROR'),
                'mappingMissing' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_MAPPING_REQUIRED'),
                'reportRow' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_ROW'),
                'reportStatus' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_STATUS'),
                'reportMessage' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_REPORT_MESSAGE'),
                'statusInserted' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_STATUS_INSERTED'),
                'statusExisting' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_STATUS_EXISTING'),
                'statusInvalid' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_STATUS_INVALID'),
                'statusError' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_STATUS_ERROR'),
                'sourceColumn' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_SOURCE_COLUMN'),
                'notMapped' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_NOT_MAPPED'),
                'encodingCp1252' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ENCODING_CP1252'),
                'encodingUtf8' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ENCODING_UTF8'),
                'invalidDetailsTitle' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_INVALID_DETAILS_JS'),
                'duplicateRowsTitle' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_ROWS_JS'),
                'duplicateGroupsTitle' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_GROUPS_JS'),
                'duplicatePrimary' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_PRIMARY'),
                'duplicateConsolidated' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_CONSOLIDATED'),
                'duplicateConflict' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_DUPLICATE_CONFLICT'),
                'organization' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_ORGANIZATION'),
                'country' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_COUNTRY'),
                'affiliation' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_AFFILIATIONS'),
            ],
            'codes' => [
                'missing_name' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_MISSING_NAME'),
                'invalid_type' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_TYPE'),
                'invalid_structure_level' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_STRUCTURE_LEVEL'),
                'invalid_operational_status' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_OPERATIONAL_STATUS'),
                'invalid_country' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_COUNTRY'),
                'invalid_email_removed' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_EMAIL'),
                'invalid_website_removed' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_WEBSITE'),
                'invalid_affiliation_type' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_AFFILIATION_TYPE'),
                'invalid_affiliation_status' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_AFFILIATION_STATUS'),
                'invalid_affiliation_start' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_AFFILIATION_START'),
                'invalid_affiliation_end' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_AFFILIATION_END'),
                'invalid_affiliation_period' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_INVALID_AFFILIATION_PERIOD'),
                'affiliation_target_not_found' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_AFFILIATION_NOT_FOUND'),
                'affiliation_target_ambiguous' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_AFFILIATION_AMBIGUOUS'),
                'affiliation_target_self' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_AFFILIATION_SELF'),
                'existing_organization' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_EXISTING'),
                'existing_identifier_conflict' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_EXISTING_CONFLICT'),
                'possible_existing_organization' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_POSSIBLE_EXISTING'),
                'organization_imported' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_IMPORTED'),
                'duplicate_conflict' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_DUPLICATE_CONFLICT'),
                'database_error' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_CODE_DATABASE_ERROR'),
            ],
            'targets' => [
                ['key' => 'name', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_NAME'), 'required' => true],
                ['key' => 'legal_name', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_LEGAL_NAME'), 'required' => false],
                ['key' => 'code', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_CODE'), 'required' => false],
                ['key' => 'type', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_TYPE'), 'required' => false],
                ['key' => 'structure_level', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_STRUCTURE_LEVEL'), 'required' => false],
                ['key' => 'operational_status', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_OPERATIONAL_STATUS'), 'required' => false],
                ['key' => 'country_code', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_COUNTRY'), 'required' => false],
                ['key' => 'city', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_CITY'), 'required' => false],
                ['key' => 'province', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_PROVINCE'), 'required' => false],
                ['key' => 'region', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_REGION'), 'required' => false],
                ['key' => 'address_line', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_ADDRESS'), 'required' => false],
                ['key' => 'postal_code', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_POSTAL_CODE'), 'required' => false],
                ['key' => 'phone', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_PHONE'), 'required' => false],
                ['key' => 'email', 'label' => Text::_('JGLOBAL_EMAIL'), 'required' => false],
                ['key' => 'website', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_WEBSITE'), 'required' => false],
                ['key' => 'vat_id', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_VAT_ID'), 'required' => false],
                ['key' => 'tax_identifier', 'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_TAX_IDENTIFIER'), 'required' => false],
                ['key' => 'affiliation_target', 'label' => Text::_('COM_XDECAROORGANIZATIONS_IMPORT_AFFILIATION_TARGET'), 'required' => false],
                ['key' => 'affiliation_type', 'label' => Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE'), 'required' => false],
                ['key' => 'affiliation_code', 'label' => Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_CODE'), 'required' => false],
                ['key' => 'affiliation_status', 'label' => Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS'), 'required' => false],
                ['key' => 'affiliation_starts_on', 'label' => Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STARTS_ON'), 'required' => false],
                ['key' => 'affiliation_ends_on', 'label' => Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_ENDS_ON'), 'required' => false],
            ],
        ]);

        ToolbarHelper::title(Text::_('COM_XDECAROORGANIZATIONS_IMPORT_TITLE'), 'upload');
        ToolbarHelper::back(Text::_('JTOOLBAR_BACK'), Route::_('index.php?option=com_xdecaroorganizations&view=organizations', false));

        parent::display($tpl);
    }
}
