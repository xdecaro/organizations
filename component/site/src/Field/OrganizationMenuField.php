<?php

namespace xdecaro\Component\Organizations\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

final class OrganizationMenuField extends ListField
{
    protected $type = 'OrganizationMenu';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('name'),
                $db->quoteName('operational_status'),
                $db->quoteName('state'),
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('state') . ' >= 0')
            ->order($db->quoteName('name') . ' ASC');

        $items = $db->setQuery($query)->loadObjectList() ?: [];

        foreach ($items as $item) {
            $status = strtolower(trim((string) ($item->operational_status ?? 'inactive')));
            $statusKey = 'COM_XDECAROORGANIZATIONS_OPERATIONAL_' . strtoupper($status);
            $statusLabel = Text::_($statusKey);

            if ($statusLabel === $statusKey) {
                $statusLabel = $status !== '' ? $status : Text::_('JUNKNOWN');
            }

            $published = (int) ($item->state ?? 0) === 1;
            $active = $status === 'active';
            $disabled = !$published || !$active;

            $label = (string) $item->name . ' — ' . $statusLabel;

            if (!$published) {
                $label .= ' · ' . Text::_('COM_XDECAROORGANIZATIONS_SITE_MENU_NOT_PUBLISHED');
            }

            $options[] = HTMLHelper::_(
                'select.option',
                (string) (int) $item->id,
                $label,
                'value',
                'text',
                $disabled
            );
        }

        return $options;
    }
}
