<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\AppointmentDomain;

final class AppointmentsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= ['person_name_snapshot', 'organization_name', 'body_name', 'role_code', 'starts_on', 'planned_ends_on'];
        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.person_name_snapshot', $direction = 'asc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.organization', (int) $this->getUserStateFromRequest($this->context . '.filter.organization', 'filter_organization', 0, 'int'));
        $this->setState('filter.body', (int) $this->getUserStateFromRequest($this->context . '.filter.body', 'filter_body', 0, 'int'));
        $this->setState('filter.role', $this->getUserStateFromRequest($this->context . '.filter.role', 'filter_role', '', 'cmd'));
        $this->setState('filter.visual_status', $this->getUserStateFromRequest($this->context . '.filter.visual_status', 'filter_visual_status', '', 'cmd'));
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'a.*',
                'o.name AS organization_name',
                'o.code AS organization_code',
                'b.name AS body_name',
                'b.code AS body_code',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_appointments', 'a'))
            ->innerJoin($db->quoteName('#__xdecaroorganizations_organizations', 'o') . ' ON o.id = a.organization_id')
            ->leftJoin($db->quoteName('#__xdecaroorganizations_bodies', 'b') . ' ON b.id = a.body_id')
            ->where($db->quoteName('a.state') . ' >= 0');

        $organizationId = max(0, (int) $this->getState('filter.organization'));
        if ($organizationId > 0) {
            $query->where($db->quoteName('a.organization_id') . ' = :organizationId')
                ->bind(':organizationId', $organizationId, ParameterType::INTEGER);
        }

        $bodyId = max(0, (int) $this->getState('filter.body'));
        if ($bodyId > 0) {
            $query->where($db->quoteName('a.body_id') . ' = :bodyId')
                ->bind(':bodyId', $bodyId, ParameterType::INTEGER);
        }

        $role = trim((string) $this->getState('filter.role'));
        if ($role !== '') {
            $query->where($db->quoteName('a.role_code') . ' = :roleCode')->bind(':roleCode', $role);
        }

        $today = Factory::getDate()->format('Y-m-d');
        $visualStatus = trim((string) $this->getState('filter.visual_status'));
        if ($visualStatus === 'scheduled') {
            $query->where($db->quoteName('a.starts_on') . ' > :statusScheduledToday')->bind(':statusScheduledToday', $today);
        } elseif ($visualStatus === 'active') {
            $query->where($db->quoteName('a.starts_on') . ' <= :statusActiveTodayStart')
                ->where('(' . $db->quoteName('a.end_reason') . ' IS NULL OR ' . $db->quoteName('a.end_reason') . " = '')")
                ->where($db->quoteName('a.ended_on') . ' IS NULL')
                ->where('(' . $db->quoteName('a.planned_ends_on') . ' IS NULL OR ' . $db->quoteName('a.planned_ends_on') . ' >= :statusActiveTodayEnd)')
                ->bind(':statusActiveTodayStart', $today)
                ->bind(':statusActiveTodayEnd', $today);
        } elseif ($visualStatus === 'expired') {
            $query->where($db->quoteName('a.planned_ends_on') . ' IS NOT NULL')
                ->where($db->quoteName('a.planned_ends_on') . ' < :statusExpiredToday')
                ->where('(' . $db->quoteName('a.end_reason') . ' IS NULL OR ' . $db->quoteName('a.end_reason') . " = '')")
                ->bind(':statusExpiredToday', $today);
        } elseif ($visualStatus === 'ended') {
            $query->where('(' . $db->quoteName('a.ended_on') . ' IS NOT NULL OR (' . $db->quoteName('a.end_reason') . ' IS NOT NULL AND ' . $db->quoteName('a.end_reason') . " <> ''))");
        }

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.person_name_snapshot') . ' LIKE :searchPerson'
                . ' OR ' . $db->quoteName('o.name') . ' LIKE :searchOrganization'
                . ' OR ' . $db->quoteName('o.code') . ' LIKE :searchOrganizationCode'
                . ' OR ' . $db->quoteName('b.name') . ' LIKE :searchBody'
                . ' OR ' . $db->quoteName('a.role_code') . ' LIKE :searchRoleCode'
                . ' OR ' . $db->quoteName('a.role_custom') . ' LIKE :searchRoleCustom)'
            )
                ->bind(':searchPerson', $like)
                ->bind(':searchOrganization', $like)
                ->bind(':searchOrganizationCode', $like)
                ->bind(':searchBody', $like)
                ->bind(':searchRoleCode', $like)
                ->bind(':searchRoleCustom', $like);
        }

        $order = (string) $this->getState('list.ordering', 'a.person_name_snapshot');
        $allowed = ['a.person_name_snapshot', 'o.name', 'b.name', 'a.role_code', 'a.starts_on', 'a.planned_ends_on'];
        if (!in_array($order, $allowed, true)) {
            $order = 'a.person_name_snapshot';
        }
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $query->order($order . ' ' . $direction . ', ' . $db->quoteName('a.id') . ' ASC');
    }

    public function getItems(): array
    {
        $items = parent::getItems();
        foreach ($items as $item) {
            $item->visual_status = AppointmentDomain::status((array) $item);
            $item->role_label_key = AppointmentDomain::roleLabelKey((string) ($item->role_code ?? ''));
        }
        return $items;
    }

    public function getOrganizationOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select([$db->quoteName('id'), $db->quoteName('name'), $db->quoteName('code')])
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))->where($db->quoteName('state') . ' >= 0')->order($db->quoteName('name') . ' ASC');
        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getBodyOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select([$db->quoteName('b.id'), $db->quoteName('b.name'), 'o.name AS organization_name'])
            ->from($db->quoteName('#__xdecaroorganizations_bodies', 'b'))
            ->innerJoin($db->quoteName('#__xdecaroorganizations_organizations', 'o') . ' ON o.id = b.organization_id')
            ->where($db->quoteName('b.state') . ' >= 0')->order($db->quoteName('o.name') . ' ASC, ' . $db->quoteName('b.name') . ' ASC');
        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getRoleOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select('DISTINCT ' . $db->quoteName('role_code'))
            ->from($db->quoteName('#__xdecaroorganizations_appointments'))->where($db->quoteName('state') . ' >= 0')->where($db->quoteName('role_code') . " <> ''")->order($db->quoteName('role_code') . ' ASC');
        return array_values(array_filter(array_map('strval', $db->setQuery($query)->loadColumn() ?: [])));
    }
}
