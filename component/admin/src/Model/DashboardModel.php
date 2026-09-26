<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\DuplicateService;

final class DashboardModel extends BaseDatabaseModel
{
    private const TYPES = [
        'organization',
        'association',
        'club',
        'federation',
        'company',
        'public_body',
        'school',
        'sponsor',
        'supplier',
    ];

    private const STRUCTURE_LEVELS = [
        'unspecified',
        'international',
        'national',
        'regional',
        'provincial',
        'local',
        'branch',
        'other',
    ];

    private const OPERATIONAL_STATUSES = [
        'active',
        'inactive',
        'represented',
        'commissaried',
        'merged',
        'dissolved',
    ];

    public function getDashboard(): array
    {
        $db = $this->getDatabase();
        $today = Factory::getDate()->format('Y-m-d');

        $counts = [
            'total' => $this->countOrganizations(),
            'published' => $this->countOrganizations($db->quoteName('state') . ' = 1'),
            'unpublished' => $this->countOrganizations($db->quoteName('state') . ' = 0'),
            'operational_active' => $this->countOrganizations($db->quoteName('operational_status') . ' = ' . $db->quote('active')),
            'bodies' => $this->countActiveBodies($today),
            'appointments_active' => $this->countActiveAppointments($today),
            'delegations_active' => $this->countActiveDelegations($today),
            'affiliations_active' => $this->countActiveAffiliations($today),
        ];

        $duplicates = 0;
        try {
            $duplicates = count((new DuplicateService($db))->find(500));
        } catch (\Throwable) {
            $duplicates = 0;
        }

        return [
            'counts' => $counts,
            'duplicates' => $duplicates,
            'type_counts' => $this->groupCounts('type', self::TYPES, 'organization'),
            'structure_counts' => $this->groupCounts('structure_level', self::STRUCTURE_LEVELS, 'unspecified'),
            'status_counts' => $this->groupCounts('operational_status', self::OPERATIONAL_STATUSES, 'active'),
            'quality_counts' => $this->qualityCounts(),
            'recent' => $this->recentOrganizations(8),
        ];
    }

    private function countOrganizations(?string $extraWhere = null): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('state') . ' >= 0');

        if ($extraWhere !== null && $extraWhere !== '') {
            $query->where($extraWhere);
        }

        return (int) $db->setQuery($query)->loadResult();
    }

    private function countActiveBodies(string $today): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_bodies', 'b'))
            ->where($db->quoteName('b.state') . ' = 1')
            ->where('(' . $db->quoteName('b.starts_on') . ' IS NULL OR ' . $db->quoteName('b.starts_on') . ' <= :todayStart)')
            ->where('(' . $db->quoteName('b.ends_on') . ' IS NULL OR ' . $db->quoteName('b.ends_on') . ' >= :todayEnd)')
            ->bind(':todayStart', $today)
            ->bind(':todayEnd', $today);

        return (int) $db->setQuery($query)->loadResult();
    }

    private function countActiveAppointments(string $today): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_appointments', 'a'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('a.starts_on') . ' <= :todayStart')
            ->where($db->quoteName('a.ended_on') . ' IS NULL')
            ->where('(' . $db->quoteName('a.planned_ends_on') . ' IS NULL OR ' . $db->quoteName('a.planned_ends_on') . ' >= :todayEnd)')
            ->bind(':todayStart', $today)
            ->bind(':todayEnd', $today);

        return (int) $db->setQuery($query)->loadResult();
    }

    private function countActiveDelegations(string $today): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_delegations', 'd'))
            ->where($db->quoteName('d.state') . ' = 1')
            ->where($db->quoteName('d.starts_on') . ' <= :todayStart')
            ->where('(' . $db->quoteName('d.ends_on') . ' IS NULL OR ' . $db->quoteName('d.ends_on') . ' >= :todayEnd)')
            ->bind(':todayStart', $today)
            ->bind(':todayEnd', $today);

        return (int) $db->setQuery($query)->loadResult();
    }

    private function countActiveAffiliations(string $today): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_affiliations', 'a'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('a.status') . ' = :status')
            ->where('(' . $db->quoteName('a.starts_on') . ' IS NULL OR ' . $db->quoteName('a.starts_on') . ' <= :todayStart)')
            ->where('(' . $db->quoteName('a.ends_on') . ' IS NULL OR ' . $db->quoteName('a.ends_on') . ' >= :todayEnd)')
            ->bind(':status', $status = 'active')
            ->bind(':todayStart', $today)
            ->bind(':todayEnd', $today);

        return (int) $db->setQuery($query)->loadResult();
    }

    private function groupCounts(string $field, array $allowedValues, string $fallback): array
    {
        $db = $this->getDatabase();
        $quotedField = $db->quoteName($field);
        $query = $db->getQuery(true)
            ->select([$quotedField . ' AS value', 'COUNT(*) AS total'])
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('state') . ' >= 0')
            ->group($quotedField);

        $result = array_fill_keys($allowedValues, 0);
        foreach ((array) $db->setQuery($query)->loadAssocList() as $row) {
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                $value = $fallback;
            }
            if (!array_key_exists($value, $result)) {
                $result[$value] = 0;
            }
            $result[$value] += (int) ($row['total'] ?? 0);
        }

        return $result;
    }

    private function qualityCounts(): array
    {
        $db = $this->getDatabase();

        return [
            'country' => $this->countOrganizations('(' . $db->quoteName('country_code') . ' IS NULL OR ' . $db->quoteName('country_code') . " = '')"),
            'code' => $this->countOrganizations('(' . $db->quoteName('code') . ' IS NULL OR ' . $db->quoteName('code') . " = '')"),
            'logo' => $this->countOrganizations('(' . $db->quoteName('logo') . ' IS NULL OR ' . $db->quoteName('logo') . " = '')"),
            'contact' => $this->countOrganizations('((' . $db->quoteName('email') . ' IS NULL OR ' . $db->quoteName('email') . " = '') AND (" . $db->quoteName('pec_email') . ' IS NULL OR ' . $db->quoteName('pec_email') . " = ''))"),
            'website' => $this->countOrganizations('(' . $db->quoteName('website') . ' IS NULL OR ' . $db->quoteName('website') . " = '')"),
            'structure' => $this->countOrganizations('(' . $db->quoteName('structure_level') . ' IS NULL OR ' . $db->quoteName('structure_level') . " = '' OR " . $db->quoteName('structure_level') . ' = ' . $db->quote('unspecified') . ')'),
        ];
    }

    private function recentOrganizations(int $limit): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('name'),
                $db->quoteName('type'),
                $db->quoteName('operational_status'),
                $db->quoteName('created'),
                $db->quoteName('modified'),
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('state') . ' >= 0')
            ->order('COALESCE(' . $db->quoteName('modified') . ', ' . $db->quoteName('created') . ') DESC, ' . $db->quoteName('id') . ' DESC');

        return (array) $db->setQuery($query, 0, max(1, min(20, $limit)))->loadAssocList();
    }
}
