<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use RuntimeException;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationAffiliationDomain;

final class OrganizationAffiliationModel extends AdminModel
{
    protected $text_prefix = 'COM_XDECAROORGANIZATIONS';

    public function getTable($type = 'OrganizationAffiliation', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        return false;
    }

    public function saveAffiliation(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $table = $this->getTable();
        $existing = null;

        if ($id > 0) {
            if (!$table->load($id)) {
                throw new RuntimeException('Affiliation not found.');
            }
            $existing = $table->getProperties();
        }

        $payload = [
            'id' => $id,
            'organization_id' => (int) ($data['organization_id'] ?? ($existing['organization_id'] ?? 0)),
            'target_organization_id' => (int) ($data['target_organization_id'] ?? ($existing['target_organization_id'] ?? 0)),
            'relation_type' => strtolower(trim((string) ($data['relation_type'] ?? ($existing['relation_type'] ?? 'sports_affiliation')))),
            'relation_code' => $this->nullableString($data['relation_code'] ?? ($existing['relation_code'] ?? null)),
            'starts_on' => $this->nullableString($data['starts_on'] ?? ($existing['starts_on'] ?? null)),
            'ends_on' => $this->nullableString($data['ends_on'] ?? ($existing['ends_on'] ?? null)),
            'status' => strtolower(trim((string) ($data['status'] ?? ($existing['status'] ?? 'active')))),
            'notes' => $this->nullableString($data['notes'] ?? ($existing['notes'] ?? null)),
            'state' => (int) ($data['state'] ?? ($existing['state'] ?? 1)),
        ];

        $errors = OrganizationAffiliationDomain::validate($payload);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_values($errors)));
        }

        if (!$this->organizationExists($payload['organization_id']) || !$this->organizationExists($payload['target_organization_id'])) {
            throw new RuntimeException('Organization not found.');
        }

        $this->assertNotDuplicate($payload, $id);

        $payload['state'] = $payload['state'] === 1 ? 1 : 0;
        $now = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if (!$existing) {
            $payload['uuid'] = self::uuidV4();
            $payload['created'] = $now;
            $payload['created_by'] = $userId;
        } else {
            $payload['modified'] = $now;
            $payload['modified_by'] = $userId;
        }

        if (!$table->bind($payload) || !$table->check() || !$table->store()) {
            throw new RuntimeException((string) ($table->getError() ?: 'Unable to save affiliation.'));
        }

        return (int) $table->id;
    }

    public function deleteAffiliation(int $id): void
    {
        if ($id < 1) {
            throw new RuntimeException('Affiliation not found.');
        }

        $table = $this->getTable();
        if (!$table->load($id)) {
            throw new RuntimeException('Affiliation not found.');
        }

        if (!$table->delete($id)) {
            throw new RuntimeException((string) ($table->getError() ?: 'Unable to delete affiliation.'));
        }
    }

    private function assertNotDuplicate(array $payload, int $id): void
    {
        if ((int) $payload['state'] !== 1 || (string) $payload['status'] !== 'active') {
            return;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_affiliations'))
            ->where($db->quoteName('organization_id') . ' = :organizationId')
            ->where($db->quoteName('target_organization_id') . ' = :targetId')
            ->where($db->quoteName('relation_type') . ' = :relationType')
            ->where($db->quoteName('status') . ' = ' . $db->quote('active'))
            ->where($db->quoteName('state') . ' = 1')
            ->bind(':organizationId', $payload['organization_id'], ParameterType::INTEGER)
            ->bind(':targetId', $payload['target_organization_id'], ParameterType::INTEGER)
            ->bind(':relationType', $payload['relation_type']);

        if ($id > 0) {
            $query->where($db->quoteName('id') . ' != :id')->bind(':id', $id, ParameterType::INTEGER);
        }

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            throw new RuntimeException('An active affiliation of the same type already exists.');
        }
    }

    private function organizationExists(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' >= 0')
            ->bind(':id', $id, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
