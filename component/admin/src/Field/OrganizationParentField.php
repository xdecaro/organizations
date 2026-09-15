<?php

namespace xdecaro\Component\Organizations\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

final class OrganizationParentField extends ListField
{
    protected $type = 'OrganizationParent';

    protected function getOptions()
    {
        $options = parent::getOptions();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $currentId = (int) ($this->form?->getValue('id') ?? 0);

        if ($currentId < 1) {
            $currentId = Factory::getApplication()->getInput()->getInt('id');
        }

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('parent_id'),
                $db->quoteName('name'),
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('state') . ' >= 0');

        $items = $db->setQuery($query)->loadObjectList() ?: [];
        $excludedIds = [];

        if ($currentId > 0) {
            $excludedIds[$currentId] = true;

            foreach (OrganizationHierarchy::descendantIds($items, $currentId) as $descendantId) {
                $excludedIds[$descendantId] = true;
            }
        }

        $available = array_values(array_filter(
            $items,
            static fn(object $item): bool => !isset($excludedIds[(int) $item->id])
        ));

        foreach (OrganizationHierarchy::order($available) as $item) {
            $depth = (int) ($item->hierarchy_depth ?? 0);
            $prefix = $depth > 0
                ? str_repeat("\u{00A0}\u{00A0}", $depth) . '↳ '
                : '';

            $options[] = HTMLHelper::_('select.option', (string) (int) $item->id, $prefix . (string) $item->name);
        }

        return $options;
    }
}
