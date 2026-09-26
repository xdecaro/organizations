<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

final class OrganizationsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'id',
            'name',
            'legal_name',
            'code',
            'type',
            'structure_level',
            'operational_status',
            'state',
            'created',
            'modified',
        ];
        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.name', $direction = 'asc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
        $this->setState('filter.type', $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'cmd'));
        $this->setState('filter.structure', $this->getUserStateFromRequest($this->context . '.filter.structure', 'filter_structure', '', 'cmd'));
        $this->setState('filter.operational', $this->getUserStateFromRequest($this->context . '.filter.operational', 'filter_operational', '', 'cmd'));
        $this->setState('filter.quality', $this->getUserStateFromRequest($this->context . '.filter.quality', 'filter_quality', '', 'cmd'));
        $this->setState('filter.relation', $this->getUserStateFromRequest($this->context . '.filter.relation', 'filter_relation', '', 'cmd'));
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
                'a.structure_level',
                'a.territory_type',
                'a.territory_name',
                'a.operational_status',
                'a.status_since',
                'a.parent_id',
                'a.email',
                'a.phone',
                'a.state',
                'a.access',
                'a.created',
                'a.modified',
                'p.name AS parent_name',
                "(SELECT COUNT(*) FROM " . $db->quoteName('#__xdecaroorganizations_affiliations', 'af')
                    . " WHERE " . $db->quoteName('af.state') . " = 1"
                    . " AND " . $db->quoteName('af.status') . " = " . $db->quote('active')
                    . " AND " . $db->quoteName('af.organization_id') . " = " . $db->quoteName('a.id') . ") AS affiliation_count",
                "(SELECT COUNT(*) FROM " . $db->quoteName('#__xdecaroorganizations_affiliations', 'afi')
                    . " WHERE " . $db->quoteName('afi.state') . " = 1"
                    . " AND " . $db->quoteName('afi.status') . " = " . $db->quote('active')
                    . " AND " . $db->quoteName('afi.target_organization_id') . " = " . $db->quoteName('a.id') . ") AS affiliate_count",
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

        $structure = trim((string) $this->getState('filter.structure'));
        $allowedStructures = ['unspecified', 'international', 'national', 'regional', 'provincial', 'local', 'branch', 'other'];
        if (in_array($structure, $allowedStructures, true)) {
            if ($structure === 'unspecified') {
                $q->where('(' . $db->quoteName('a.structure_level') . ' = :structure OR ' . $db->quoteName('a.structure_level') . " = '' OR " . $db->quoteName('a.structure_level') . ' IS NULL)')
                    ->bind(':structure', $structure);
            } else {
                $q->where($db->quoteName('a.structure_level') . ' = :structure')->bind(':structure', $structure);
            }
        }

        $operational = trim((string) $this->getState('filter.operational'));
        $allowedOperational = ['active', 'inactive', 'represented', 'commissaried', 'merged', 'dissolved'];
        if (in_array($operational, $allowedOperational, true)) {
            $q->where($db->quoteName('a.operational_status') . ' = :operational')->bind(':operational', $operational);
        }

        $quality = trim((string) $this->getState('filter.quality'));
        if ($quality !== '') {
            $this->applyQualityFilter($q, $quality);
        }

        $relation = trim((string) $this->getState('filter.relation'));
        if ($relation !== '') {
            $this->applyRelationFilter($q, $relation);
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
        $allowed = ['a.id', 'a.name', 'a.legal_name', 'a.code', 'a.type', 'a.structure_level', 'a.operational_status', 'a.state', 'a.created', 'a.modified'];
        if (!in_array($order, $allowed, true)) {
            $order = 'a.name';
        }

        $direction = strtoupper((string) $this->state->get('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $q->order($order . ' ' . $direction);
    }

    private function applyQualityFilter(DatabaseQuery $query, string $quality): void
    {
        $db = $this->getDatabase();
        $condition = match ($quality) {
            'country' => '(' . $db->quoteName('a.country_code') . ' IS NULL OR ' . $db->quoteName('a.country_code') . " = '')",
            'code' => '(' . $db->quoteName('a.code') . ' IS NULL OR ' . $db->quoteName('a.code') . " = '')",
            'logo' => '(' . $db->quoteName('a.logo') . ' IS NULL OR ' . $db->quoteName('a.logo') . " = '')",
            'contact' => '((' . $db->quoteName('a.email') . ' IS NULL OR ' . $db->quoteName('a.email') . " = '') AND (" . $db->quoteName('a.pec_email') . ' IS NULL OR ' . $db->quoteName('a.pec_email') . " = ''))",
            'website' => '(' . $db->quoteName('a.website') . ' IS NULL OR ' . $db->quoteName('a.website') . " = '')",
            'structure' => '(' . $db->quoteName('a.structure_level') . ' IS NULL OR ' . $db->quoteName('a.structure_level') . " = '' OR " . $db->quoteName('a.structure_level') . ' = ' . $db->quote('unspecified') . ')',
            default => '',
        };

        if ($condition !== '') {
            $query->where($condition);
        }
    }

    private function applyRelationFilter(DatabaseQuery $query, string $relation): void
    {
        $db = $this->getDatabase();
        $today = Factory::getDate()->format('Y-m-d');

        $exists = match ($relation) {
            'bodies' => 'SELECT 1 FROM ' . $db->quoteName('#__xdecaroorganizations_bodies', 'rb')
                . ' WHERE ' . $db->quoteName('rb.organization_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('rb.state') . ' = 1'
                . ' AND (' . $db->quoteName('rb.starts_on') . ' IS NULL OR ' . $db->quoteName('rb.starts_on') . ' <= ' . $db->quote($today) . ')'
                . ' AND (' . $db->quoteName('rb.ends_on') . ' IS NULL OR ' . $db->quoteName('rb.ends_on') . ' >= ' . $db->quote($today) . ')',
            'appointments' => 'SELECT 1 FROM ' . $db->quoteName('#__xdecaroorganizations_appointments', 'ra')
                . ' WHERE ' . $db->quoteName('ra.organization_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('ra.state') . ' = 1'
                . ' AND ' . $db->quoteName('ra.starts_on') . ' <= ' . $db->quote($today)
                . ' AND ' . $db->quoteName('ra.ended_on') . ' IS NULL'
                . ' AND (' . $db->quoteName('ra.planned_ends_on') . ' IS NULL OR ' . $db->quoteName('ra.planned_ends_on') . ' >= ' . $db->quote($today) . ')',
            'delegations' => 'SELECT 1 FROM ' . $db->quoteName('#__xdecaroorganizations_delegations', 'rd')
                . ' WHERE ' . $db->quoteName('rd.organization_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('rd.state') . ' = 1'
                . ' AND ' . $db->quoteName('rd.starts_on') . ' <= ' . $db->quote($today)
                . ' AND (' . $db->quoteName('rd.ends_on') . ' IS NULL OR ' . $db->quoteName('rd.ends_on') . ' >= ' . $db->quote($today) . ')',
            'affiliations' => 'SELECT 1 FROM ' . $db->quoteName('#__xdecaroorganizations_affiliations', 'rf')
                . ' WHERE ' . $db->quoteName('rf.organization_id') . ' = ' . $db->quoteName('a.id')
                . ' AND ' . $db->quoteName('rf.state') . ' = 1'
                . ' AND ' . $db->quoteName('rf.status') . ' = ' . $db->quote('active')
                . ' AND (' . $db->quoteName('rf.starts_on') . ' IS NULL OR ' . $db->quoteName('rf.starts_on') . ' <= ' . $db->quote($today) . ')'
                . ' AND (' . $db->quoteName('rf.ends_on') . ' IS NULL OR ' . $db->quoteName('rf.ends_on') . ' >= ' . $db->quote($today) . ')',
            default => '',
        };

        if ($exists !== '') {
            $query->where('EXISTS (' . $exists . ')');
        }
    }
}
