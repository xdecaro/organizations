<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationHierarchy;

final class HierarchyModel extends ListModel
{
    private ?array $preparedItems = null;
    private ?array $diagnostics = null;

    public function __construct($config = [])
    {
        $config['filter_fields'] ??= ['a.name', 'a.code', 'a.type', 'a.structure_level', 'a.operational_status'];
        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.name', $direction = 'asc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.type', $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'cmd'));
        $this->setState('filter.structure', $this->getUserStateFromRequest($this->context . '.filter.structure', 'filter_structure', '', 'cmd'));
        $this->setState('filter.operational', $this->getUserStateFromRequest($this->context . '.filter.operational', 'filter_operational', '', 'cmd'));
        $this->setState('filter.roots_only', (int) $this->getUserStateFromRequest($this->context . '.filter.roots_only', 'filter_roots_only', 0, 'int'));
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'a.id','a.uuid','a.name','a.code','a.type','a.structure_level','a.operational_status','a.parent_id','a.state','a.access',
                'p.name AS parent_name',
                '(SELECT COUNT(*) FROM ' . $db->quoteName('#__xdecaroorganizations_organizations', 'c') . ' WHERE c.parent_id = a.id AND c.state >= 0) AS child_count',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'a'))
            ->leftJoin($db->quoteName('#__xdecaroorganizations_organizations', 'p') . ' ON p.id = a.parent_id')
            ->where($db->quoteName('a.state') . ' >= 0');

        $type = trim((string) $this->getState('filter.type'));
        if ($type !== '') { $query->where($db->quoteName('a.type') . ' = :type')->bind(':type', $type); }
        $structure = trim((string) $this->getState('filter.structure'));
        if ($structure !== '') { $query->where($db->quoteName('a.structure_level') . ' = :structure')->bind(':structure', $structure); }
        $operational = trim((string) $this->getState('filter.operational'));
        if ($operational !== '') { $query->where($db->quoteName('a.operational_status') . ' = :operational')->bind(':operational', $operational); }
        if ((int) $this->getState('filter.roots_only') === 1) { $query->where($db->quoteName('a.parent_id') . ' IS NULL'); }

        return $query->order($db->quoteName('a.name') . ' ASC, ' . $db->quoteName('a.id') . ' ASC');
    }

    private function prepare(): array
    {
        if ($this->preparedItems !== null) { return $this->preparedItems; }
        $db = $this->getDatabase();
        $db->setQuery($this->getListQuery());
        $items = $db->loadObjectList() ?: [];
        $this->diagnostics = OrganizationHierarchy::analyze($items);

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            $needle = mb_strtolower($search, 'UTF-8');
            $byId = [];
            foreach ($items as $item) { $byId[(int) $item->id] = $item; }
            $keep = [];
            foreach ($items as $item) {
                $haystack = mb_strtolower(trim((string) ($item->name ?? '') . ' ' . (string) ($item->code ?? '')), 'UTF-8');
                if (!str_contains($haystack, $needle)) { continue; }
                $cursor = (int) $item->id;
                for ($depth = 0; $depth < 100 && $cursor > 0 && isset($byId[$cursor]); $depth++) {
                    if (isset($keep[$cursor])) { break; }
                    $keep[$cursor] = true;
                    $cursor = (int) ($byId[$cursor]->parent_id ?? 0);
                }
            }
            $items = array_values(array_filter($items, static fn($item): bool => isset($keep[(int) $item->id])));
        }

        $this->preparedItems = OrganizationHierarchy::flattenWithDepth($items);
        return $this->preparedItems;
    }

    public function getItems(): array
    {
        $items = $this->prepare();
        $start = max(0, (int) $this->getState('list.start', 0));
        $limit = (int) $this->getState('list.limit', 20);
        return $limit > 0 ? array_slice($items, $start, $limit) : $items;
    }

    public function getTotal(): int
    {
        return count($this->prepare());
    }

    public function getDiagnostics(): array
    {
        $this->prepare();
        return $this->diagnostics ?: ['self_parent' => [], 'cycles' => [], 'missing_parent' => [], 'unreachable' => []];
    }

    public function getTypeOptions(): array
    {
        return $this->distinctOptions('type');
    }

    public function getStructureOptions(): array
    {
        return $this->distinctOptions('structure_level');
    }

    public function getOperationalOptions(): array
    {
        return $this->distinctOptions('operational_status');
    }

    private function distinctOptions(string $column): array
    {
        $allowed = ['type','structure_level','operational_status'];
        if (!in_array($column, $allowed, true)) { return []; }
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select('DISTINCT ' . $db->quoteName($column))
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('state') . ' >= 0')->where($db->quoteName($column) . " <> ''")->order($db->quoteName($column) . ' ASC');
        return array_values(array_filter(array_map('strval', $db->setQuery($query)->loadColumn() ?: [])));
    }
}
