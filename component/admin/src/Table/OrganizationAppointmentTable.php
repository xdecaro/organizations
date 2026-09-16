<?php

namespace xdecaro\Component\Organizations\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class OrganizationAppointmentTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__xdecaroorganizations_appointments', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        $this->organization_id = (int) ($this->organization_id ?? 0);
        $this->person_uuid = strtolower(trim((string) ($this->person_uuid ?? '')));
        $this->person_name_snapshot = trim((string) ($this->person_name_snapshot ?? ''));
        $this->role_code = trim((string) ($this->role_code ?? ''));
        $this->starts_on = trim((string) ($this->starts_on ?? ''));

        foreach (['role_custom', 'planned_ends_on', 'ended_on', 'end_reason', 'end_note', 'notes', 'modified'] as $field) {
            if (property_exists($this, $field) && $this->{$field} !== null) {
                $value = trim((string) $this->{$field});
                $this->{$field} = $value !== '' ? $value : null;
            }
        }

        if ($this->organization_id < 1) {
            $this->setError('Organization is required.');
            return false;
        }

        if ($this->person_uuid === '') {
            $this->setError('Person UUID is required.');
            return false;
        }

        if ($this->person_name_snapshot === '') {
            $this->setError('Person name snapshot is required.');
            return false;
        }

        if ($this->role_code === '') {
            $this->setError('Appointment role is required.');
            return false;
        }

        if ($this->starts_on === '') {
            $this->setError('Appointment start date is required.');
            return false;
        }

        return parent::check();
    }
}
