<?php

namespace xdecaro\Component\Organizations\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class OrganizationDelegationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__xdecaroorganizations_delegations', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        $this->organization_id = (int) ($this->organization_id ?? 0);
        $this->appointment_id = (int) ($this->appointment_id ?? 0);
        $this->title = trim((string) ($this->title ?? ''));
        $this->starts_on = trim((string) ($this->starts_on ?? ''));

        foreach (['scope', 'ends_on', 'notes', 'modified'] as $field) {
            if (property_exists($this, $field) && $this->{$field} !== null) {
                $value = trim((string) $this->{$field});
                $this->{$field} = $value !== '' ? $value : null;
            }
        }

        if ($this->organization_id < 1 || $this->appointment_id < 1) {
            $this->setError('Organization and appointment are required.');
            return false;
        }

        if ($this->title === '' || $this->starts_on === '') {
            $this->setError('Delegation title and start date are required.');
            return false;
        }

        return parent::check();
    }
}
