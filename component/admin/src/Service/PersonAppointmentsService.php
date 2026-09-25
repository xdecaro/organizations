<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;

/**
 * Read-only public view of Organizations appointments keyed by People UUID.
 */
final class PersonAppointmentsService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getAppointmentsByPersonUuid(string $personUuid): array
    {
        $personUuid = strtolower(trim($personUuid));

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $personUuid)) {
            return [];
        }

        $user = Factory::getApplication()->getIdentity();
        if (
            !$user->authorise('organizations.view_appointments', CoreIntegrationService::COMPONENT)
            && !$user->authorise('core.admin', CoreIntegrationService::COMPONENT)
        ) {
            throw new RuntimeException('Not authorised to query organization appointments.', 403);
        }

        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('a.id'),
                $this->db->quoteName('a.uuid'),
                $this->db->quoteName('a.organization_id'),
                $this->db->quoteName('a.role_code'),
                $this->db->quoteName('a.role_custom'),
                $this->db->quoteName('a.starts_on'),
                $this->db->quoteName('a.planned_ends_on'),
                $this->db->quoteName('a.ended_on'),
                $this->db->quoteName('a.end_reason'),
                $this->db->quoteName('o.uuid', 'organization_uuid'),
                $this->db->quoteName('o.name', 'organization_name'),
            ])
            ->from($this->db->quoteName('#__xdecaroorganizations_appointments', 'a'))
            ->join(
                'INNER',
                $this->db->quoteName('#__xdecaroorganizations_organizations', 'o')
                . ' ON ' . $this->db->quoteName('o.id') . ' = ' . $this->db->quoteName('a.organization_id')
            )
            ->where($this->db->quoteName('a.state') . ' >= 0')
            ->where($this->db->quoteName('o.state') . ' >= 0')
            ->where($this->db->quoteName('a.person_uuid') . ' = :personUuid')
            ->bind(':personUuid', $personUuid, ParameterType::STRING)
            ->order($this->db->quoteName('a.starts_on') . ' DESC, ' . $this->db->quoteName('a.id') . ' DESC');

        $rows = array_values((array) $this->db->setQuery($query)->loadAssocList());
        $appointments = [];

        foreach ($rows as $row) {
            $status = AppointmentDomain::status($row);
            $appointments[] = [
                'appointment_id' => (int) ($row['id'] ?? 0),
                'appointment_uuid' => (string) ($row['uuid'] ?? ''),
                'organization_id' => (int) ($row['organization_id'] ?? 0),
                'organization_uuid' => (string) ($row['organization_uuid'] ?? ''),
                'organization_name' => (string) ($row['organization_name'] ?? ''),
                'role_code' => (string) ($row['role_code'] ?? ''),
                'role_custom' => (string) ($row['role_custom'] ?? ''),
                'role_label_key' => AppointmentDomain::roleLabelKey((string) ($row['role_code'] ?? '')),
                'starts_on' => (string) ($row['starts_on'] ?? ''),
                'planned_ends_on' => (string) ($row['planned_ends_on'] ?? ''),
                'ended_on' => (string) ($row['ended_on'] ?? ''),
                'end_reason' => (string) ($row['end_reason'] ?? ''),
                'visual_status' => $status,
                'is_current' => $status === 'active',
            ];
        }

        usort(
            $appointments,
            static function (array $left, array $right): int {
                $currentOrder = ((int) $right['is_current']) <=> ((int) $left['is_current']);
                if ($currentOrder !== 0) {
                    return $currentOrder;
                }

                $startOrder = strcmp((string) $right['starts_on'], (string) $left['starts_on']);
                if ($startOrder !== 0) {
                    return $startOrder;
                }

                return ((int) $right['appointment_id']) <=> ((int) $left['appointment_id']);
            }
        );

        return $appointments;
    }
}
