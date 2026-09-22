<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use RuntimeException;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationBodyDomain;

final class OrganizationBodyModel extends AdminModel
{
    protected $text_prefix = 'COM_XDECAROORGANIZATIONS';

    public function getTable($type = 'OrganizationBody', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        return false;
    }

    public function saveBody(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $table = $this->getTable();
        $existing = null;

        if ($id > 0) {
            if (!$table->load($id)) {
                throw new RuntimeException('Organization body not found.');
            }
            $existing = $table->getProperties();
        }

        $organizationId = (int) ($data['organization_id'] ?? ($existing['organization_id'] ?? 0));
        if (!$this->organizationExists($organizationId)) {
            throw new RuntimeException('Organization not found.');
        }

        $parentId = (int) ($data['parent_id'] ?? ($existing['parent_id'] ?? 0));
        $parentId = $parentId > 0 ? $parentId : null;

        if (!$this->validParent($id, $organizationId, $parentId)) {
            throw new RuntimeException((string) ($this->getError() ?: 'Invalid organization body parent.'));
        }

        $payload = [
            'id' => $id,
            'organization_id' => $organizationId,
            'parent_id' => $parentId,
            'name' => trim((string) ($data['name'] ?? ($existing['name'] ?? ''))),
            'code' => $this->nullableString($data['code'] ?? ($existing['code'] ?? null)),
            'body_type' => strtolower(trim((string) ($data['body_type'] ?? ($existing['body_type'] ?? 'other')))),
            'starts_on' => $this->nullableString($data['starts_on'] ?? ($existing['starts_on'] ?? null)),
            'ends_on' => $this->nullableString($data['ends_on'] ?? ($existing['ends_on'] ?? null)),
            'notes' => $this->nullableString($data['notes'] ?? ($existing['notes'] ?? null)),
            'state' => (int) ($data['state'] ?? ($existing['state'] ?? 1)),
        ];

        $errors = OrganizationBodyDomain::validate($payload);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_values($errors)));
        }

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
            throw new RuntimeException((string) ($table->getError() ?: 'Unable to save organization body.'));
        }

        return (int) $table->id;
    }

    public function deleteBody(int $id): void
    {
        if ($id < 1) {
            throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_BODY_DELETE_NOT_FOUND'));
        }

        $db = $this->getDatabase();
        $db->transactionStart();

        try {
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('name'),
                ])
                ->from($db->quoteName('#__xdecaroorganizations_bodies'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER)
                ->forUpdate();

            $body = $db->setQuery($query, 0, 1)->loadObject();
            if (!$body) {
                throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_BODY_DELETE_NOT_FOUND'));
            }

            $childCount = $this->dependencyCount('#__xdecaroorganizations_bodies', 'parent_id', $id);
            $appointmentCount = $this->dependencyCount('#__xdecaroorganizations_appointments', 'body_id', $id);

            if ($childCount > 0 || $appointmentCount > 0) {
                throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_BODY_DELETE_BLOCKED'));
            }

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__xdecaroorganizations_bodies'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $db->setQuery($query)->execute();

            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();
            throw $e;
        }
    }

    private function dependencyCount(string $table, string $field, int $id): int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName($table))
            ->where($db->quoteName($field) . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult();
    }

    private function organizationExists(int $organizationId): bool
    {
        if ($organizationId < 1) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $organizationId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function validParent(int $id, int $organizationId, ?int $parentId): bool
    {
        if (!$parentId) {
            return true;
        }

        if ($id > 0 && $id === $parentId) {
            $this->setError('An organization body cannot be its own parent.');
            return false;
        }

        $db = $this->getDatabase();
        $seen = [];
        $cursor = $parentId;

        for ($i = 0; $i < 50 && $cursor > 0; $i++) {
            if (isset($seen[$cursor]) || ($id > 0 && $cursor === $id)) {
                $this->setError('Organization body hierarchy would create a cycle.');
                return false;
            }

            $seen[$cursor] = true;
            $query = $db->getQuery(true)
                ->select([$db->quoteName('organization_id'), $db->quoteName('parent_id')])
                ->from($db->quoteName('#__xdecaroorganizations_bodies'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $cursor, ParameterType::INTEGER);
            $row = $db->setQuery($query, 0, 1)->loadAssoc();

            if (!$row || (int) $row['organization_id'] !== $organizationId) {
                $this->setError('Parent body must belong to the same organization.');
                return false;
            }

            $cursor = (int) ($row['parent_id'] ?? 0);
        }

        if ($cursor > 0) {
            $this->setError('Organization body hierarchy is too deep or invalid.');
            return false;
        }

        return true;
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
