<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class AppointmentMembershipPolicyService
{
    private const REQUIREMENTS = [
        'inherit',
        'none',
        'active_member',
        'active_member_fee_current',
    ];

    public function __construct(
        private DatabaseInterface $db,
        private MembershipIntegrationService $membership
    ) {
    }

    public static function normalizeRequirement(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, self::REQUIREMENTS, true) ? $value : 'inherit';
    }

    public function resolveRequirement(int $organizationId): string
    {
        if ($organizationId < 1) {
            return 'none';
        }

        $seen = [];
        $cursor = $organizationId;

        for ($depth = 0; $depth < 100 && $cursor > 0; $depth++) {
            if (isset($seen[$cursor])) {
                return 'none';
            }

            $seen[$cursor] = true;
            $query = $this->db->getQuery(true)
                ->select([
                    $this->db->quoteName('parent_id'),
                    $this->db->quoteName('appointment_membership_requirement'),
                ])
                ->from($this->db->quoteName('#__xdecaroorganizations_organizations'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->where($this->db->quoteName('state') . ' >= 0')
                ->bind(':id', $cursor, ParameterType::INTEGER);

            $row = $this->db->setQuery($query, 0, 1)->loadAssoc();
            if (!$row) {
                return 'none';
            }

            $requirement = self::normalizeRequirement((string) ($row['appointment_membership_requirement'] ?? 'inherit'));
            if ($requirement !== 'inherit') {
                return $requirement;
            }

            $cursor = (int) ($row['parent_id'] ?? 0);
        }

        return 'none';
    }

    public function evaluate(int $organizationId, string $personUuid): array
    {
        $requirement = $this->resolveRequirement($organizationId);
        $result = $this->membership->evaluate($personUuid, $requirement);
        $result['requirement'] = $requirement;

        return $result;
    }
}
