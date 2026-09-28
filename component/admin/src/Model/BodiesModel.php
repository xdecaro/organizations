<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationBodyDomain;

final class BodiesModel extends ListModel
{
    public function __construct($config = [])
    {
        $config['filter_fields'] ??= ['b.name', 'o.name', 'b.body_type', 'b.starts_on', 'b.ends_on'];
        parent::__construct($config);
    }

    protected function populateState($ordering = 'o.name', $direction = 'asc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.organization', (int) $this->getUserStateFromRequest($this->context . '.filter.organization', 'filter_organization', 0, 'int'));
        $this->setState('filter.body_type', $this->getUserStateFromRequest($this->context . '.filter.body_type', 'filter_body_type', '', 'cmd'));
        $this->setState('filter.visual_status', $this->getUserStateFromRequest($this->context . '.filter.visual_status', 'filter_visual_status', '', 'cmd'));
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $count = '(SELECT COUNT(*) FROM ' . $db->quoteName('#__xdecaroorganizations_appointments', 'ac')
            . ' WHERE ac.body_id = b.id AND ac.state >= 0)';
        $query = $db->getQuery(true)
            ->select([
                'b.*',
                'o.name AS organization_name',
                'o.code AS organization_code',
                'p.name AS parent_name',
                $count . ' AS appointment_count',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_bodies', 'b'))
            ->innerJoin($db->quoteName('#__xdecaroorganizations_organizations', 'o') . ' ON o.id = b.organization_id')
            ->leftJoin($db->quoteName('#__xdecaroorganizations_bodies', 'p') . ' ON p.id = b.parent_id')
            ->where($db->quoteName('b.state') . ' >= 0');

        $organizationId = max(0, (int) $this->getState('filter.organization'));
        if ($organizationId > 0) {
            $query->where($db->quoteName('b.organization_id') . ' = :organizationId')
                ->bind(':organizationId', $organizationId, ParameterType::INTEGER);
        }

        $bodyType = trim((string) $this->getState('filter.body_type'));
        if ($bodyType !== '') {
            $query->where($db->quoteName('b.body_type') . ' = :bodyType')->bind(':bodyType', $bodyType);
        }

        $visualStatus = trim((string) $this->getState('filter.visual_status'));
        if ($visualStatus === 'active') {
            $query->where($db->quoteName('b.state') . ' = 1');
        } elseif ($visualStatus === 'inactive') {
            $query->where($db->quoteName('b.state') . ' = 0');
        }

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('b.name') . ' LIKE :searchBody'
                . ' OR ' . $db->quoteName('b.code') . ' LIKE :searchBodyCode'
                . ' OR ' . $db->quoteName('o.name') . ' LIKE :searchOrganization'
                . ' OR ' . $db->quoteName('o.code') . ' LIKE :searchOrganizationCode'
                . ' OR ' . $db->quoteName('p.name') . ' LIKE :searchParent)'
            )
                ->bind(':searchBody', $like)
                ->bind(':searchBodyCode', $like)
                ->bind(':searchOrganization', $like)
                ->bind(':searchOrganizationCode', $like)
                ->bind(':searchParent', $like);
        }

        $order = (string) $this->getState('list.ordering', 'o.name');
        $allowed = ['b.name','o.name','b.body_type','b.starts_on','b.ends_on'];
        if (!in_array($order, $allowed, true)) { $order = 'o.name'; }
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        return $query->order($order . ' ' . $direction . ', ' . $db->quoteName('b.name') . ' ASC');
    }

    public function getItems(): array
    {
        $items = parent::getItems();
        foreach ($items as $item) {
            $item->visual_status = OrganizationBodyDomain::status($item);
            $item->type_label_key = OrganizationBodyDomain::typeLabelKey((string) ($item->body_type ?? 'other'));
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

    public function getBodyTypeOptions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select('DISTINCT ' . $db->quoteName('body_type'))
            ->from($db->quoteName('#__xdecaroorganizations_bodies'))->where($db->quoteName('state') . ' >= 0')->where($db->quoteName('body_type') . " <> ''")->order($db->quoteName('body_type') . ' ASC');
        return array_values(array_filter(array_map('strval', $db->setQuery($query)->loadColumn() ?: [])));
    }
}
