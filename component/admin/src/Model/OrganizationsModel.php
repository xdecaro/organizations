<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

final class OrganizationsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= ['id', 'name', 'legal_name', 'code', 'type', 'state', 'created'];
        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.name', $direction = 'asc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
        $this->setState('filter.type', $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'cmd'));
        parent::populateState($ordering, $direction);
    }

    public function getItems()
    {
        $db = $this->getDatabase();
        $db->setQuery($this->getListQuery());
        $items = OrganizationHierarchy::order($db->loadObjectList() ?: []);

        $start = max(0, (int) $this->getState('list.start', 0));
        $limit = (int) $this->getState('list.limit', 0);

        return $limit > 0 ? array_slice($items, $start, $limit) : $items;
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $q = $db->getQuery(true)
            ->select([
                'a.id',
                'a.uuid',
                'a.name',
                'a.legal_name',
                'a.code',
                'a.type',
                'a.parent_id',
                'a.email',
                'a.phone',
                'a.state',
                'a.access',
                'a.created',
                'p.name AS parent_name',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'a'))
            ->leftJoin($db->quoteName('#__xdecaroorganizations_organizations', 'p') . ' ON p.id=a.parent_id');

        $state = $this->getState('filter.state');
        if ($state !== '') {
            $state = (int) $state;
            $q->where($db->quoteName('a.state') . ' = :state')->bind(':state', $state, ParameterType::INTEGER);
        } else {
            $q->where($db->quoteName('a.state') . ' >= 0');
        }

        $type = trim((string) $this->getState('filter.type'));
        if ($type !== '') {
            $q->where($db->quoteName('a.type') . ' = :type')->bind(':type', $type);
        }

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $q->where(
                '(' . $db->quoteName('a.name') . ' LIKE :s1 OR '
                . $db->quoteName('a.legal_name') . ' LIKE :s2 OR '
                . $db->quoteName('a.code') . ' LIKE :s3 OR '
                . $db->quoteName('a.email') . ' LIKE :s4)'
            )
                ->bind(':s1', $like)
                ->bind(':s2', $like)
                ->bind(':s3', $like)
                ->bind(':s4', $like);
        }

        $order = (string) $this->state->get('list.ordering', 'a.name');
        $allowed = ['a.id', 'a.name', 'a.legal_name', 'a.code', 'a.type', 'a.state', 'a.created'];
        if (!in_array($order, $allowed, true)) {
            $order = 'a.name';
        }

        $direction = strtoupper((string) $this->state->get('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $q->order($order . ' ' . $direction);
    }
}
