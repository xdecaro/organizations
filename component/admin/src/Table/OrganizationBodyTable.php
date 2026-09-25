<?php

namespace xdecaro\Component\Organizations\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class OrganizationBodyTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__xdecaroorganizations_bodies', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        $this->organization_id = (int) ($this->organization_id ?? 0);
        $this->parent_id = !empty($this->parent_id) ? (int) $this->parent_id : null;
        $this->name = trim((string) ($this->name ?? ''));
        $this->body_type = strtolower(trim((string) ($this->body_type ?? 'other')));

        foreach (['code', 'starts_on', 'ends_on', 'notes', 'modified'] as $field) {
            if (property_exists($this, $field) && $this->{$field} !== null) {
                $value = trim((string) $this->{$field});
                $this->{$field} = $value !== '' ? $value : null;
            }
        }

        if ($this->organization_id < 1) {
            $this->setError('Organization is required.');
            return false;
        }

        if ($this->name === '') {
            $this->setError('Body name is required.');
            return false;
        }

        return parent::check();
    }
}
