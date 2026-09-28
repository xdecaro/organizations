<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationAffiliationDomain;

final class AffiliationsModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= [
            'source_name',
            'target_name',
            'relation_type',
            'status',
            'starts_on',
            'ends_on',
            'relation_code',
        ];

        parent::__construct($config);
    }

    protected function populateState($ordering = 's.name', $direction = 'asc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.source', (int) $this->getUserStateFromRequest($this->context . '.filter.source', 'filter_source', 0, 'int'));
        $this->setState('filter.target', (int) $this->getUserStateFromRequest($this->context . '.filter.target', 'filter_target', 0, 'int'));
        $this->setState('filter.relation_type', $this->getUserStateFromRequest($this->context . '.filter.relation_type', 'filter_relation_type', '', 'cmd'));
        $this->setState('filter.status', $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'cmd'));
        $this->setState('filter.temporal', $this->getUserStateFromRequest($this->context . '.filter.temporal', 'filter_temporal', '', 'cmd'));
        $this->setState('filter.perspective', $this->getUserStateFromRequest($this->context . '.filter.perspective', 'filter_perspective', 'all', 'cmd'));

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'a.*',
                's.name AS source_name',
                's.code AS source_code',
                's.type AS source_type',
                't.name AS target_name',
                't.code AS target_code',
                't.type AS target_type',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_affiliations', 'a'))
            ->innerJoin($db->quoteName('#__xdecaroorganizations_organizations', 's') . ' ON s.id = a.organization_id')
            ->innerJoin($db->quoteName('#__xdecaroorganizations_organizations', 't') . ' ON t.id = a.target_organization_id')
            ->where($db->quoteName('a.state') . ' >= 0');

        $sourceId = max(0, (int) $this->getState('filter.source'));
        if ($sourceId > 0) {
            $query->where($db->quoteName('a.organization_id') . ' = :sourceId')
                ->bind(':sourceId', $sourceId, ParameterType::INTEGER);
        }

        $targetId = max(0, (int) $this->getState('filter.target'));
        if ($targetId > 0) {
            $query->where($db->quoteName('a.target_organization_id') . ' = :targetId')
                ->bind(':targetId', $targetId, ParameterType::INTEGER);
        }

        $relationType = trim((string) $this->getState('filter.relation_type'));
        if (in_array($relationType, OrganizationAffiliationDomain::TYPES, true)) {
            $query->where($db->quoteName('a.relation_type') . ' = :relationType')
                ->bind(':relationType', $relationType);
        }

        $status = trim((string) $this->getState('filter.status'));
        if (in_array($status, OrganizationAffiliationDomain::STATUSES, true)) {
            $query->where($db->quoteName('a.status') . ' = :status')
                ->bind(':status', $status);
        }

        $temporal = trim((string) $this->getState('filter.temporal'));
        $today = Factory::getDate()->format('Y-m-d');
        if ($temporal === 'current') {
            $query->where('(' . $db->quoteName('a.starts_on') . ' IS NULL OR ' . $db->quoteName('a.starts_on') . ' <= :todayCurrentStart)')
                ->where('(' . $db->quoteName('a.ends_on') . ' IS NULL OR ' . $db->quoteName('a.ends_on') . ' >= :todayCurrentEnd)')
                ->bind(':todayCurrentStart', $today)
                ->bind(':todayCurrentEnd', $today);
        } elseif ($temporal === 'expired') {
            $query->where($db->quoteName('a.ends_on') . ' IS NOT NULL')
                ->where($db->quoteName('a.ends_on') . ' < :todayExpired')
                ->bind(':todayExpired', $today);
        }

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('s.name') . ' LIKE :searchSourceName'
                . ' OR ' . $db->quoteName('s.code') . ' LIKE :searchSourceCode'
                . ' OR ' . $db->quoteName('t.name') . ' LIKE :searchTargetName'
                . ' OR ' . $db->quoteName('t.code') . ' LIKE :searchTargetCode'
                . ' OR ' . $db->quoteName('a.relation_code') . ' LIKE :searchRelationCode)'
            )
                ->bind(':searchSourceName', $like)
                ->bind(':searchSourceCode', $like)
                ->bind(':searchTargetName', $like)
                ->bind(':searchTargetCode', $like)
                ->bind(':searchRelationCode', $like);
        }

        $perspective = (string) $this->getState('filter.perspective', 'all');
        $defaultOrder = $perspective === 'affiliates' ? 't.name' : 's.name';
        $order = (string) $this->getState('list.ordering', $defaultOrder);
        $allowedOrder = [
            's.name',
            't.name',
            'a.relation_type',
            'a.status',
            'a.starts_on',
            'a.ends_on',
            'a.relation_code',
        ];
        if (!in_array($order, $allowedOrder, true)) {
            $order = $defaultOrder;
        }
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $query->order($order . ' ' . $direction . ', ' . $db->quoteName('a.id') . ' ASC');
    }

    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $item) {
            $item->type_label_key = OrganizationAffiliationDomain::typeLabelKey((string) ($item->relation_type ?? 'other'));
            $item->status_label_key = OrganizationAffiliationDomain::statusLabelKey((string) ($item->status ?? 'inactive'));
        }

        return $items;
    }

    public function getSourceOrganizationOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'DISTINCT ' . $db->quoteName('o.id'),
                $db->quoteName('o.name'),
                $db->quoteName('o.code'),
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'o'))
            ->innerJoin(
                $db->quoteName('#__xdecaroorganizations_affiliations', 'a')
                . ' ON ' . $db->quoteName('a.organization_id') . ' = ' . $db->quoteName('o.id')
            )
            ->where($db->quoteName('o.state') . ' >= 0')
            ->where($db->quoteName('a.state') . ' >= 0')
            ->order($db->quoteName('o.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getTargetOrganizationOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'DISTINCT ' . $db->quoteName('o.id'),
                $db->quoteName('o.name'),
                $db->quoteName('o.code'),
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'o'))
            ->innerJoin(
                $db->quoteName('#__xdecaroorganizations_affiliations', 'a')
                . ' ON ' . $db->quoteName('a.target_organization_id') . ' = ' . $db->quoteName('o.id')
            )
            ->where($db->quoteName('o.state') . ' >= 0')
            ->where($db->quoteName('a.state') . ' >= 0')
            ->order($db->quoteName('o.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }
}
