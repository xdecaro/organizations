<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use RuntimeException;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationDelegationDomain;

final class OrganizationDelegationModel extends AdminModel
{
    protected $text_prefix = 'COM_XDECAROORGANIZATIONS';

    public function getTable($type = 'OrganizationDelegation', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        return false;
    }

    public function saveDelegation(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $table = $this->getTable();
        $existing = null;

        if ($id > 0) {
            if (!$table->load($id)) {
                throw new RuntimeException('Organization delegation not found.');
            }
            $existing = $table->getProperties();
        }

        $organizationId = (int) ($data['organization_id'] ?? ($existing['organization_id'] ?? 0));
        $appointmentId = (int) ($data['appointment_id'] ?? ($existing['appointment_id'] ?? 0));
        $appointment = $this->appointment($appointmentId, $organizationId);

        if (!$appointment) {
            throw new RuntimeException('Selected appointment does not belong to this organization.');
        }

        $payload = [
            'id' => $id,
            'organization_id' => $organizationId,
            'appointment_id' => $appointmentId,
            'title' => trim((string) ($data['title'] ?? ($existing['title'] ?? ''))),
            'scope' => $this->nullableString($data['scope'] ?? ($existing['scope'] ?? null)),
            'starts_on' => trim((string) ($data['starts_on'] ?? ($existing['starts_on'] ?? ''))),
            'ends_on' => $this->nullableString($data['ends_on'] ?? ($existing['ends_on'] ?? null)),
            'notes' => $this->nullableString($data['notes'] ?? ($existing['notes'] ?? null)),
            'state' => (int) ($data['state'] ?? ($existing['state'] ?? 1)),
        ];

        $errors = OrganizationDelegationDomain::validate($payload);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_values($errors)));
        }

        $this->validateAgainstAppointment($payload, $appointment);
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
            throw new RuntimeException((string) ($table->getError() ?: 'Unable to save organization delegation.'));
        }

        return (int) $table->id;
    }

    private function appointment(int $appointmentId, int $organizationId): ?array
    {
        if ($appointmentId < 1 || $organizationId < 1) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'id',
                'organization_id',
                'starts_on',
                'planned_ends_on',
                'ended_on',
                'end_reason',
                'state',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_appointments'))
            ->where($db->quoteName('id') . ' = :appointmentId')
            ->where($db->quoteName('organization_id') . ' = :organizationId')
            ->where($db->quoteName('state') . ' >= 0')
            ->bind(':appointmentId', $appointmentId, ParameterType::INTEGER)
            ->bind(':organizationId', $organizationId, ParameterType::INTEGER);

        $row = $db->setQuery($query, 0, 1)->loadAssoc();

        return $row ?: null;
    }

    private function validateAgainstAppointment(array $delegation, array $appointment): void
    {
        $appointmentStart = trim((string) ($appointment['starts_on'] ?? ''));
        $delegationStart = trim((string) ($delegation['starts_on'] ?? ''));
        $delegationEnd = trim((string) ($delegation['ends_on'] ?? ''));
        $appointmentEnd = trim((string) ($appointment['ended_on'] ?? ''));

        if ($appointmentStart !== '' && $delegationStart < $appointmentStart) {
            throw new RuntimeException('Delegation cannot start before the linked appointment.');
        }

        if ($appointmentEnd !== '') {
            if ($delegationStart > $appointmentEnd) {
                throw new RuntimeException('Delegation cannot start after the linked appointment ended.');
            }

            if ($delegationEnd === '' || $delegationEnd > $appointmentEnd) {
                throw new RuntimeException('Delegation linked to an ended appointment must also end no later than that appointment.');
            }
        }
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
