<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;

final class OrganizationDelegationsService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getDelegation(int|string $id): ?array
    {
        $this->authorise();
        $query = $this->baseQuery();

        if (is_int($id) || ctype_digit((string) $id)) {
            $value = (int) $id;
            $query->where($this->db->quoteName('d.id') . ' = :id')
                ->bind(':id', $value, ParameterType::INTEGER);
        } else {
            $value = strtolower(trim((string) $id));
            $query->where($this->db->quoteName('d.uuid') . ' = :uuid')
                ->bind(':uuid', $value);
        }

        $row = $this->db->setQuery($query, 0, 1)->loadAssoc();

        return $row ?: null;
    }

    public function getDelegationsByOrganization(int|string $organization, bool $includeInactive = false): array
    {
        $this->authorise();
        $organizationId = $this->resolveOrganizationId($organization);

        if ($organizationId < 1) {
            return [];
        }

        $query = $this->baseQuery()
            ->where($this->db->quoteName('d.organization_id') . ' = :organizationId')
            ->bind(':organizationId', $organizationId, ParameterType::INTEGER)
            ->order($this->db->quoteName('d.starts_on') . ' DESC, ' . $this->db->quoteName('d.title') . ' ASC');

        if (!$includeInactive) {
            $query->where($this->db->quoteName('d.state') . ' = 1');
        }

        return array_values((array) $this->db->setQuery($query)->loadAssocList());
    }

    private function baseQuery()
    {
        return $this->db->getQuery(true)
            ->select([
                'd.id',
                'd.uuid',
                'd.organization_id',
                'd.appointment_id',
                'd.title',
                'd.scope',
                'd.starts_on',
                'd.ends_on',
                'd.state',
                'a.person_uuid',
                'a.person_name_snapshot',
                'a.role_code',
                'a.role_custom',
                'a.body_id',
                'b.uuid AS body_uuid',
                'b.name AS body_name',
                'b.body_type',
            ])
            ->from($this->db->quoteName('#__xdecaroorganizations_delegations', 'd'))
            ->innerJoin($this->db->quoteName('#__xdecaroorganizations_appointments', 'a') . ' ON a.id = d.appointment_id')
            ->leftJoin($this->db->quoteName('#__xdecaroorganizations_bodies', 'b') . ' ON b.id = a.body_id')
            ->where($this->db->quoteName('d.state') . ' >= 0');
    }

    private function resolveOrganizationId(int|string $organization): int
    {
        if (is_int($organization) || ctype_digit((string) $organization)) {
            return max(0, (int) $organization);
        }

        $uuid = strtolower(trim((string) $organization));
        if ($uuid === '') {
            return 0;
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($this->db->quoteName('uuid') . ' = :uuid')
            ->bind(':uuid', $uuid);

        return (int) $this->db->setQuery($query, 0, 1)->loadResult();
    }

    private function authorise(): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('organizations.view_delegations', CoreIntegrationService::COMPONENT)
            && !$user->authorise('core.manage', CoreIntegrationService::COMPONENT)
            && !$user->authorise('core.admin', CoreIntegrationService::COMPONENT)) {
            throw new RuntimeException('Not authorised to query organization delegations.', 403);
        }
    }
}
