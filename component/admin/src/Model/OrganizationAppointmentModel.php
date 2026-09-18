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
use xdecaro\Component\Organizations\Administrator\Service\AppointmentDomain;
use xdecaro\Component\Organizations\Administrator\Service\AppointmentMembershipPolicyService;
use xdecaro\Component\Organizations\Administrator\Service\PeopleIntegrationService;

final class OrganizationAppointmentModel extends AdminModel
{
    protected $text_prefix = 'COM_XDECAROORGANIZATIONS';

    public function getTable($type = 'OrganizationAppointment', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        return false;
    }

    public function saveAppointment(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $table = $this->getTable();
        $existing = null;

        if ($id > 0) {
            if (!$table->load($id)) {
                throw new RuntimeException('Appointment not found.');
            }
            $existing = $table->getProperties();
        }

        $organizationId = (int) ($data['organization_id'] ?? ($existing['organization_id'] ?? 0));
        if (!$this->organizationExists($organizationId)) {
            throw new RuntimeException('Organization not found.');
        }

        $bodyId = (int) ($data['body_id'] ?? ($existing['body_id'] ?? 0));
        $bodyId = $bodyId > 0 ? $bodyId : null;
        if ($bodyId !== null && !$this->bodyBelongsToOrganization($bodyId, $organizationId)) {
            throw new RuntimeException('Selected body does not belong to this organization.');
        }

        $personUuid = strtolower(trim((string) ($data['person_uuid'] ?? ($existing['person_uuid'] ?? ''))));
        $roleCode = trim((string) ($data['role_code'] ?? ($existing['role_code'] ?? '')));
        $roleCustom = trim((string) ($data['role_custom'] ?? ($existing['role_custom'] ?? '')));
        $startsOn = trim((string) ($data['starts_on'] ?? ($existing['starts_on'] ?? '')));
        $providedEnd = trim((string) ($data['planned_ends_on'] ?? ($existing['planned_ends_on'] ?? '')));
        $durationYears = isset($data['duration_years']) && $data['duration_years'] !== ''
            ? (int) $data['duration_years']
            : null;
        $plannedEndsOn = AppointmentDomain::plannedEnd($startsOn, $durationYears, $providedEnd ?: null);

        if ($roleCode !== 'custom') {
            $roleCustom = '';
        }

        $payload = [
            'id' => $id,
            'organization_id' => $organizationId,
            'body_id' => $bodyId,
            'person_uuid' => $personUuid,
            'role_code' => $roleCode,
            'role_custom' => $roleCustom !== '' ? $roleCustom : null,
            'starts_on' => $startsOn,
            'planned_ends_on' => $plannedEndsOn,
            'ended_on' => $existing['ended_on'] ?? null,
            'end_reason' => $existing['end_reason'] ?? null,
            'end_note' => $existing['end_note'] ?? null,
            'notes' => $this->nullableString($data['notes'] ?? ($existing['notes'] ?? null)),
            'state' => (int) ($existing['state'] ?? 1),
        ];

        $errors = AppointmentDomain::validate($payload);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_values($errors)));
        }

        $personChanged = !$existing || strtolower((string) ($existing['person_uuid'] ?? '')) !== $personUuid;

        if (!$existing || $personChanged) {
            $this->assertMembershipEligibility($organizationId, $personUuid);
        }

        $snapshot = trim((string) ($existing['person_name_snapshot'] ?? ''));

        if ($personChanged) {
            $person = $this->people()->getPerson($personUuid);
            if (!$person) {
                throw new RuntimeException('People is unavailable or the selected person cannot be resolved.');
            }
            $snapshot = $this->personSnapshot($person);
        }

        if ($snapshot === '') {
            throw new RuntimeException('Person name snapshot is required.');
        }

        $payload['person_name_snapshot'] = $snapshot;
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
            throw new RuntimeException((string) ($table->getError() ?: 'Unable to save appointment.'));
        }

        return (int) $table->id;
    }

    public function endAppointment(int $id, string $reason, string $endedOn, ?string $note): bool
    {
        $table = $this->getTable();
        if ($id < 1 || !$table->load($id)) {
            throw new RuntimeException('Appointment not found.');
        }

        $data = $table->getProperties();
        $data['ended_on'] = trim($endedOn);
        $data['end_reason'] = trim($reason);

        $errors = AppointmentDomain::validate($data);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', array_values($errors)));
        }

        $table->ended_on = $data['ended_on'];
        $table->end_reason = $data['end_reason'];
        $table->end_note = $this->nullableString($note);
        $table->modified = Factory::getDate()->toSql();
        $table->modified_by = (int) Factory::getApplication()->getIdentity()->id;

        $db = $this->getDatabase();
        $db->transactionStart();

        try {
            if (!$table->check() || !$table->store()) {
                throw new RuntimeException((string) ($table->getError() ?: 'Unable to end appointment.'));
            }

            $this->endDelegations($id, $data['ended_on'], (int) $table->modified_by, (string) $table->modified);
            $db->transactionCommit();
        } catch (\Throwable $exception) {
            $db->transactionRollback();
            throw $exception;
        }

        return true;
    }

    public function deleteAppointment(int $id): bool
    {
        $table = $this->getTable();
        if ($id < 1 || !$table->load($id)) {
            throw new RuntimeException('Appointment not found.');
        }

        $status = AppointmentDomain::status($table->getProperties());
        if (!in_array($status, ['active', 'scheduled'], true)) {
            throw new RuntimeException('Only active or scheduled appointments can be deleted.');
        }

        if ($this->hasDelegations($id)) {
            throw new RuntimeException('This appointment has delegations and cannot be deleted. End the appointment instead.');
        }

        if (!$table->delete($id)) {
            throw new RuntimeException((string) ($table->getError() ?: 'Unable to delete appointment.'));
        }

        return true;
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

    private function bodyBelongsToOrganization(int $bodyId, int $organizationId): bool
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_bodies'))
            ->where($db->quoteName('id') . ' = :bodyId')
            ->where($db->quoteName('organization_id') . ' = :organizationId')
            ->where($db->quoteName('state') . ' >= 0')
            ->bind(':bodyId', $bodyId, ParameterType::INTEGER)
            ->bind(':organizationId', $organizationId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function endDelegations(int $appointmentId, string $endedOn, int $userId, string $modified): void
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__xdecaroorganizations_delegations'))
            ->set($db->quoteName('ends_on') . ' = :endedOn')
            ->set($db->quoteName('modified') . ' = :modified')
            ->set($db->quoteName('modified_by') . ' = :modifiedBy')
            ->where($db->quoteName('appointment_id') . ' = :appointmentId')
            ->where($db->quoteName('state') . ' >= 0')
            ->where($db->quoteName('starts_on') . ' <= :endedOnStart')
            ->where('(' . $db->quoteName('ends_on') . ' IS NULL OR ' . $db->quoteName('ends_on') . ' > :endedOnLimit)')
            ->bind(':endedOn', $endedOn)
            ->bind(':modified', $modified)
            ->bind(':modifiedBy', $userId, ParameterType::INTEGER)
            ->bind(':appointmentId', $appointmentId, ParameterType::INTEGER)
            ->bind(':endedOnStart', $endedOn)
            ->bind(':endedOnLimit', $endedOn);

        $db->setQuery($query)->execute();

        $inactiveState = 0;
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__xdecaroorganizations_delegations'))
            ->set($db->quoteName('state') . ' = :inactiveState')
            ->set($db->quoteName('modified') . ' = :modifiedFuture')
            ->set($db->quoteName('modified_by') . ' = :modifiedByFuture')
            ->where($db->quoteName('appointment_id') . ' = :appointmentIdFuture')
            ->where($db->quoteName('state') . ' >= 0')
            ->where($db->quoteName('starts_on') . ' > :endedOnFuture')
            ->bind(':inactiveState', $inactiveState, ParameterType::INTEGER)
            ->bind(':modifiedFuture', $modified)
            ->bind(':modifiedByFuture', $userId, ParameterType::INTEGER)
            ->bind(':appointmentIdFuture', $appointmentId, ParameterType::INTEGER)
            ->bind(':endedOnFuture', $endedOn);

        $db->setQuery($query)->execute();
    }

    private function hasDelegations(int $appointmentId): bool
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecaroorganizations_delegations'))
            ->where($db->quoteName('appointment_id') . ' = :appointmentId')
            ->bind(':appointmentId', $appointmentId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function assertMembershipEligibility(int $organizationId, string $personUuid): void
    {
        $result = $this->appointmentMembershipPolicy()->evaluate($organizationId, $personUuid);
        $requirement = (string) ($result['requirement'] ?? 'none');

        if ($requirement === 'none') {
            return;
        }

        if (($result['available'] ?? false) !== true) {
            throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_UNAVAILABLE_ERROR'));
        }

        if (($result['eligible'] ?? false) === true) {
            return;
        }

        $messageKey = match ((string) ($result['status'] ?? '')) {
            'not_member' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_NOT_MEMBER_ERROR',
            'inactive_member' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_INACTIVE_ERROR',
            'fee_not_current' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_FEE_ERROR',
            default => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_NOT_ELIGIBLE_ERROR',
        };

        throw new RuntimeException(Text::_($messageKey));
    }

    private function appointmentMembershipPolicy(): AppointmentMembershipPolicyService
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaroorganizations');

        if (!method_exists($component, 'getAppointmentMembershipPolicyService')) {
            throw new RuntimeException(Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_UNAVAILABLE_ERROR'));
        }

        return $component->getAppointmentMembershipPolicyService();
    }

    private function people(): PeopleIntegrationService
    {
        $component = Factory::getApplication()->bootComponent('com_xdecaroorganizations');
        if (!method_exists($component, 'getPeopleIntegrationService')) {
            throw new RuntimeException('People integration service is unavailable.');
        }

        return $component->getPeopleIntegrationService();
    }

    private function personSnapshot(array $person): string
    {
        $displayName = trim((string) ($person['display_name'] ?? ''));
        if ($displayName !== '') {
            return $displayName;
        }

        $preferredName = trim((string) ($person['preferred_name'] ?? ''));
        if ($preferredName !== '') {
            return $preferredName;
        }

        $fullName = trim(trim((string) ($person['first_name'] ?? '')) . ' ' . trim((string) ($person['last_name'] ?? '')));
        if ($fullName !== '') {
            return $fullName;
        }

        throw new RuntimeException('People returned no usable display name.');
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
