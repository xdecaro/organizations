<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\AppointmentDomain;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationDelegationDomain;

final class DelegationsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= ['d.title', 'a.person_name_snapshot', 'o.name', 'd.starts_on', 'd.ends_on'];
        parent::__construct($config);
    }

    protected function populateState($ordering = 'd.starts_on', $direction = 'desc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.organization', (int) $this->getUserStateFromRequest($this->context . '.filter.organization', 'filter_organization', 0, 'int'));
        $this->setState('filter.visual_status', $this->getUserStateFromRequest($this->context . '.filter.visual_status', 'filter_visual_status', '', 'cmd'));
        $this->setState('filter.temporal', $this->getUserStateFromRequest($this->context . '.filter.temporal', 'filter_temporal', '', 'cmd'));
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'd.*',
                'a.person_name_snapshot',
                'a.role_code',
                'a.role_custom',
                'a.planned_ends_on AS appointment_planned_ends_on',
                'a.ended_on AS appointment_ended_on',
                'b.name AS body_name',
                'o.name AS organization_name',
                'o.code AS organization_code',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_delegations', 'd'))
            ->innerJoin($db->quoteName('#__xdecaroorganizations_appointments', 'a') . ' ON a.id = d.appointment_id')
            ->innerJoin($db->quoteName('#__xdecaroorganizations_organizations', 'o') . ' ON o.id = d.organization_id')
            ->leftJoin($db->quoteName('#__xdecaroorganizations_bodies', 'b') . ' ON b.id = a.body_id')
            ->where($db->quoteName('d.state') . ' >= 0');

        $organizationId = max(0, (int) $this->getState('filter.organization'));
        if ($organizationId > 0) {
            $query->where($db->quoteName('d.organization_id') . ' = :organizationId')
                ->bind(':organizationId', $organizationId, ParameterType::INTEGER);
        }

        $todayDate = Factory::getDate();
        $today = $todayDate->format('Y-m-d');
        $soon = (clone $todayDate)->modify('+30 days')->format('Y-m-d');
        $temporal = trim((string) $this->getState('filter.temporal'));
        if ($temporal === 'current') {
            $query->where($db->quoteName('d.starts_on') . ' <= :currentTodayStart')
                ->where('(' . $db->quoteName('d.ends_on') . ' IS NULL OR ' . $db->quoteName('d.ends_on') . ' >= :currentTodayEnd)')
                ->bind(':currentTodayStart', $today)
                ->bind(':currentTodayEnd', $today);
        } elseif ($temporal === 'expired') {
            $query->where($db->quoteName('d.ends_on') . ' IS NOT NULL')
                ->where($db->quoteName('d.ends_on') . ' < :expiredToday')
                ->bind(':expiredToday', $today);
        } elseif ($temporal === 'expiring') {
            $query->where($db->quoteName('d.ends_on') . ' IS NOT NULL')
                ->where($db->quoteName('d.ends_on') . ' >= :expiringToday')
                ->where($db->quoteName('d.ends_on') . ' <= :expiringSoon')
                ->bind(':expiringToday', $today)
                ->bind(':expiringSoon', $soon);
        }

        $visualStatus = trim((string) $this->getState('filter.visual_status'));
        if ($visualStatus === 'active') {
            $query->where($db->quoteName('d.state') . ' = 1');
        } elseif ($visualStatus === 'inactive') {
            $query->where($db->quoteName('d.state') . ' = 0');
        }

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('d.title') . ' LIKE :searchTitle'
                . ' OR ' . $db->quoteName('d.scope') . ' LIKE :searchScope'
                . ' OR ' . $db->quoteName('a.person_name_snapshot') . ' LIKE :searchPerson'
                . ' OR ' . $db->quoteName('o.name') . ' LIKE :searchOrganization'
                . ' OR ' . $db->quoteName('o.code') . ' LIKE :searchOrganizationCode)'
            )
                ->bind(':searchTitle', $like)
                ->bind(':searchScope', $like)
                ->bind(':searchPerson', $like)
                ->bind(':searchOrganization', $like)
                ->bind(':searchOrganizationCode', $like);
        }

        $order = (string) $this->getState('list.ordering', 'd.starts_on');
        $allowed = ['d.title', 'a.person_name_snapshot', 'o.name', 'd.starts_on', 'd.ends_on'];
        if (!in_array($order, $allowed, true)) {
            $order = 'd.starts_on';
        }
        $direction = strtoupper((string) $this->getState('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        return $query->order($order . ' ' . $direction . ', ' . $db->quoteName('d.id') . ' DESC');
    }

    public function getItems(): array
    {
        $items = parent::getItems();
        foreach ($items as $item) {
            $item->effective_end = OrganizationDelegationDomain::effectiveEnd($item);
            $item->visual_status = OrganizationDelegationDomain::status($item);
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
}
