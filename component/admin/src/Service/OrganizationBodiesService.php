<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;

final class OrganizationBodiesService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function getBody(int|string $id): ?array
    {
        $this->authorise();

        $query = $this->db->getQuery(true)
            ->select($this->columns())
            ->from($this->db->quoteName('#__xdecaroorganizations_bodies', 'b'))
            ->where($this->db->quoteName('b.state') . ' >= 0');

        if (is_int($id) || ctype_digit((string) $id)) {
            $value = (int) $id;
            $query->where($this->db->quoteName('b.id') . ' = :id')
                ->bind(':id', $value, ParameterType::INTEGER);
        } else {
            $value = strtolower(trim((string) $id));
            $query->where($this->db->quoteName('b.uuid') . ' = :uuid')
                ->bind(':uuid', $value);
        }

        $row = $this->db->setQuery($query, 0, 1)->loadAssoc();

        return $row ?: null;
    }

    public function getBodiesByOrganization(int|string $organization, bool $includeInactive = false): array
    {
        $this->authorise();
        $organizationId = $this->resolveOrganizationId($organization);

        if ($organizationId < 1) {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select($this->columns())
            ->from($this->db->quoteName('#__xdecaroorganizations_bodies', 'b'))
            ->where($this->db->quoteName('b.organization_id') . ' = :organizationId')
            ->bind(':organizationId', $organizationId, ParameterType::INTEGER)
            ->order($this->db->quoteName('b.body_type') . ' ASC, ' . $this->db->quoteName('b.name') . ' ASC');

        if ($includeInactive) {
            $query->where($this->db->quoteName('b.state') . ' >= 0');
        } else {
            $query->where($this->db->quoteName('b.state') . ' = 1');
        }

        return array_values((array) $this->db->setQuery($query)->loadAssocList());
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

    private function columns(): array
    {
        return [
            'b.id',
            'b.uuid',
            'b.organization_id',
            'b.parent_id',
            'b.name',
            'b.code',
            'b.body_type',
            'b.starts_on',
            'b.ends_on',
            'b.state',
        ];
    }

    private function authorise(): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('organizations.view_bodies', CoreIntegrationService::COMPONENT)
            && !$user->authorise('core.manage', CoreIntegrationService::COMPONENT)
            && !$user->authorise('core.admin', CoreIntegrationService::COMPONENT)) {
            throw new RuntimeException('Not authorised to query organization bodies.', 403);
        }
    }
}
