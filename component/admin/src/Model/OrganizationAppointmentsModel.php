<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\AppointmentDomain;

final class OrganizationAppointmentsModel extends ListModel
{
    private int $organizationId = 0;

    public function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = max(0, $organizationId);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('a') . '.*')
            ->from($db->quoteName('#__xdecaroorganizations_appointments', 'a'))
            ->where($db->quoteName('a.state') . ' >= 0')
            ->order($db->quoteName('a.role_code') . ' ASC, ' . $db->quoteName('a.person_name_snapshot') . ' ASC, ' . $db->quoteName('a.starts_on') . ' ASC');

        if ($this->organizationId < 1) {
            return $query->where('1 = 0');
        }

        $query->where($db->quoteName('a.organization_id') . ' = :organizationId')
            ->bind(':organizationId', $this->organizationId, ParameterType::INTEGER);

        return $query;
    }

    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $item) {
            $item->visual_status = AppointmentDomain::status((array) $item);
            $item->role_label_key = AppointmentDomain::roleLabelKey((string) $item->role_code);
        }

        return $items;
    }
}
