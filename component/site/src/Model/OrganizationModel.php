<?php

namespace xdecaro\Component\Organizations\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

final class OrganizationModel extends BaseDatabaseModel
{
    private ?object $item = null;

    public function getItem(): ?object
    {
        if ($this->item !== null) {
            return $this->item;
        }

        $id = $this->organizationId();
        if ($id < 1) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'o.id',
                'o.name',
                'o.legal_name',
                'o.type',
                'o.structure_level',
                'o.territory_type',
                'o.territory_name',
                'o.operational_status',
                'o.parent_id',
                'o.email',
                'o.phone',
                'o.website',
                'o.facebook_url',
                'o.instagram_url',
                'o.youtube_url',
                'o.linkedin_url',
                'o.tiktok_url',
                'o.logo',
                'o.language',
                'o.access',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'o'))
            ->where($db->quoteName('o.id') . ' = :id')
            ->where($db->quoteName('o.state') . ' = 1')
            ->where($db->quoteName('o.operational_status') . ' = ' . $db->quote('active'))
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->applyPublicAccess($query, 'o');

        $this->item = $db->setQuery($query, 0, 1)->loadObject() ?: null;

        return $this->item;
    }

    public function getHierarchyPath(): array
    {
        $item = $this->getItem();
        if (!$item) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['o.id', 'o.parent_id', 'o.name'])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'o'))
            ->where($db->quoteName('o.state') . ' = 1')
            ->where($db->quoteName('o.operational_status') . ' = ' . $db->quote('active'));

        $this->applyPublicAccess($query, 'o');
        $rows = $db->setQuery($query)->loadObjectList('id') ?: [];

        $path = [];
        $seen = [];
        $cursor = (int) ($item->parent_id ?? 0);

        while ($cursor > 0 && isset($rows[$cursor]) && !isset($seen[$cursor]) && count($path) < 100) {
            $seen[$cursor] = true;
            $path[] = $rows[$cursor];
            $cursor = (int) ($rows[$cursor]->parent_id ?? 0);
        }

        return array_reverse($path);
    }

    public function getChildren(): array
    {
        $id = $this->organizationId();
        if ($id < 1) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['o.id', 'o.name', 'o.structure_level', 'o.territory_name'])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'o'))
            ->where($db->quoteName('o.parent_id') . ' = :parentId')
            ->where($db->quoteName('o.state') . ' = 1')
            ->where($db->quoteName('o.operational_status') . ' = ' . $db->quote('active'))
            ->bind(':parentId', $id, ParameterType::INTEGER)
            ->order($db->quoteName('o.name') . ' ASC');

        $this->applyPublicAccess($query, 'o');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getBodies(): array
    {
        $id = $this->organizationId();
        if ($id < 1) {
            return [];
        }

        $today = Factory::getDate()->format('Y-m-d');
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['b.id', 'b.name', 'b.body_type', 'b.parent_id'])
            ->from($db->quoteName('#__xdecaroorganizations_bodies', 'b'))
            ->where($db->quoteName('b.organization_id') . ' = :organizationId')
            ->where($db->quoteName('b.state') . ' = 1')
            ->where('(' . $db->quoteName('b.starts_on') . ' IS NULL OR ' . $db->quoteName('b.starts_on') . ' <= :todayStart)')
            ->where('(' . $db->quoteName('b.ends_on') . ' IS NULL OR ' . $db->quoteName('b.ends_on') . ' >= :todayEnd)')
            ->bind(':organizationId', $id, ParameterType::INTEGER)
            ->bind(':todayStart', $today)
            ->bind(':todayEnd', $today)
            ->order($db->quoteName('b.body_type') . ' ASC, ' . $db->quoteName('b.name') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getAppointments(): array
    {
        $id = $this->organizationId();
        if ($id < 1) {
            return [];
        }

        $today = Factory::getDate()->format('Y-m-d');
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'a.id',
                'a.person_name_snapshot',
                'a.role_code',
                'a.role_custom',
                'a.body_id',
                'a.starts_on',
                'a.planned_ends_on',
                'b.name AS body_name',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_appointments', 'a'))
            ->leftJoin($db->quoteName('#__xdecaroorganizations_bodies', 'b') . ' ON b.id = a.body_id')
            ->where($db->quoteName('a.organization_id') . ' = :organizationId')
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('a.show_on_frontend') . ' = 1')
            ->where($db->quoteName('a.starts_on') . ' <= :todayStart')
            ->where('(' . $db->quoteName('a.ended_on') . ' IS NULL OR ' . $db->quoteName('a.ended_on') . ' >= :todayEnded)')
            ->where('(' . $db->quoteName('a.planned_ends_on') . ' IS NULL OR ' . $db->quoteName('a.planned_ends_on') . ' >= :todayPlanned)')
            ->where(
                '(' . $db->quoteName('a.body_id') . ' IS NULL OR ('
                . $db->quoteName('b.state') . ' = 1'
                . ' AND (' . $db->quoteName('b.starts_on') . ' IS NULL OR ' . $db->quoteName('b.starts_on') . ' <= :todayBodyStart)'
                . ' AND (' . $db->quoteName('b.ends_on') . ' IS NULL OR ' . $db->quoteName('b.ends_on') . ' >= :todayBodyEnd)'
                . '))'
            )
            ->bind(':organizationId', $id, ParameterType::INTEGER)
            ->bind(':todayStart', $today)
            ->bind(':todayEnded', $today)
            ->bind(':todayPlanned', $today)
            ->bind(':todayBodyStart', $today)
            ->bind(':todayBodyEnd', $today)
            ->order($db->quoteName('b.name') . ' ASC, ' . $db->quoteName('a.role_code') . ' ASC, ' . $db->quoteName('a.person_name_snapshot') . ' ASC');

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    public function getDelegations(): array
    {
        $id = $this->organizationId();
        if ($id < 1) {
            return [];
        }

        $today = Factory::getDate()->format('Y-m-d');
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'd.id',
                'd.title',
                'd.scope',
                'd.starts_on',
                'd.ends_on',
                'a.person_name_snapshot',
                'a.role_code',
                'a.role_custom',
                'a.planned_ends_on AS appointment_planned_ends_on',
                'a.ended_on AS appointment_ended_on',
                'b.name AS body_name',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_delegations', 'd'))
            ->innerJoin($db->quoteName('#__xdecaroorganizations_appointments', 'a') . ' ON a.id = d.appointment_id')
            ->leftJoin($db->quoteName('#__xdecaroorganizations_bodies', 'b') . ' ON b.id = a.body_id')
            ->where($db->quoteName('d.organization_id') . ' = :organizationId')
            ->where($db->quoteName('d.state') . ' = 1')
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('a.show_on_frontend') . ' = 1')
            ->where($db->quoteName('d.starts_on') . ' <= :todayStart')
            ->where('(' . $db->quoteName('d.ends_on') . ' IS NULL OR ' . $db->quoteName('d.ends_on') . ' >= :todayDelegation)')
            ->where('(' . $db->quoteName('a.ended_on') . ' IS NULL OR ' . $db->quoteName('a.ended_on') . ' >= :todayActual)')
            ->where('(' . $db->quoteName('a.planned_ends_on') . ' IS NULL OR ' . $db->quoteName('a.planned_ends_on') . ' >= :todayPlanned)')
            ->where(
                '(' . $db->quoteName('a.body_id') . ' IS NULL OR ('
                . $db->quoteName('b.state') . ' = 1'
                . ' AND (' . $db->quoteName('b.starts_on') . ' IS NULL OR ' . $db->quoteName('b.starts_on') . ' <= :todayDelegationBodyStart)'
                . ' AND (' . $db->quoteName('b.ends_on') . ' IS NULL OR ' . $db->quoteName('b.ends_on') . ' >= :todayDelegationBodyEnd)'
                . '))'
            )
            ->bind(':organizationId', $id, ParameterType::INTEGER)
            ->bind(':todayStart', $today)
            ->bind(':todayDelegation', $today)
            ->bind(':todayActual', $today)
            ->bind(':todayPlanned', $today)
            ->bind(':todayDelegationBodyStart', $today)
            ->bind(':todayDelegationBodyEnd', $today)
            ->order($db->quoteName('d.title') . ' ASC');

        $items = $db->setQuery($query)->loadObjectList() ?: [];

        foreach ($items as $item) {
            $ends = array_filter([
                trim((string) ($item->ends_on ?? '')),
                trim((string) ($item->appointment_ended_on ?? '')),
                trim((string) ($item->appointment_planned_ends_on ?? '')),
            ]);
            sort($ends);
            $item->effective_end = $ends[0] ?? '';
        }

        return $items;
    }

    private function organizationId(): int
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        // Joomla menu items have their own generic "id". Keep the selected
        // organization in an explicit request variable to avoid collisions.
        $id = $input->getInt('organization_id', 0);

        // Backward-compatible direct links from the public directory use id.
        if ($id < 1) {
            $id = $input->getInt('id', 0);
        }

        if ($id < 1) {
            $params = $app->getParams();
            $id = (int) $params->get('organization_id', 0);

            // Compatibility with any previously stored menu configuration.
            if ($id < 1) {
                $id = (int) $params->get('id', 0);
            }
        }

        return max(0, $id);
    }

    private function applyPublicAccess($query, string $alias): void
    {
        $db = $this->getDatabase();
        $levels = array_values(array_unique(array_map('intval', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels())));

        if ($levels === []) {
            $query->where('1 = 0');
            return;
        }

        $query->whereIn($db->quoteName($alias . '.access'), $levels);

        $language = Factory::getApplication()->getLanguage()->getTag();
        $query->where(
            '(' . $db->quoteName($alias . '.language') . ' IS NULL'
            . ' OR ' . $db->quoteName($alias . '.language') . ' = ' . $db->quote('')
            . ' OR ' . $db->quoteName($alias . '.language') . ' = ' . $db->quote('*')
            . ' OR ' . $db->quoteName($alias . '.language') . ' = ' . $db->quote($language)
            . ')'
        );
    }
}
