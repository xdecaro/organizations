<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\AppointmentDomain;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationDelegationDomain;

final class OrganizationDelegationsModel extends ListModel
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
            ->select([
                'd.*',
                'a.person_name_snapshot',
                'a.role_code',
                'a.role_custom',
                'a.body_id',
                'a.planned_ends_on AS appointment_planned_ends_on',
                'a.ended_on AS appointment_ended_on',
                'b.name AS body_name',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_delegations', 'd'))
            ->innerJoin($db->quoteName('#__xdecaroorganizations_appointments', 'a') . ' ON a.id = d.appointment_id')
            ->leftJoin($db->quoteName('#__xdecaroorganizations_bodies', 'b') . ' ON b.id = a.body_id')
            ->where($db->quoteName('d.state') . ' >= 0')
            ->order($db->quoteName('d.starts_on') . ' DESC, ' . $db->quoteName('d.title') . ' ASC');

        if ($this->organizationId < 1) {
            return $query->where('1 = 0');
        }

        $query->where($db->quoteName('d.organization_id') . ' = :organizationId')
            ->bind(':organizationId', $this->organizationId, ParameterType::INTEGER);

        return $query;
    }

    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $item) {
            $item->effective_end = OrganizationDelegationDomain::effectiveEnd($item);
            $item->visual_status = OrganizationDelegationDomain::status($item);
            $item->role_label_key = AppointmentDomain::roleLabelKey((string) $item->role_code);
        }

        return $items;
    }
}
