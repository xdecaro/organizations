<?php

namespace xdecaro\Component\Organizations\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class OrganizationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__xdecaroorganizations_organizations', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        $this->name = trim((string) $this->name);

        if ($this->name === '') {
            $this->setError('Organization name is required.');
            return false;
        }

        foreach (['legal_name', 'code', 'vat_id', 'tax_identifier', 'email', 'pec_email', 'phone', 'website', 'address_line', 'postal_code', 'city', 'region', 'language', 'logo', 'notes'] as $field) {
            if (property_exists($this, $field) && $this->{$field} !== null) {
                $value = trim((string) $this->{$field});
                $this->{$field} = $value !== '' ? $value : null;
            }
        }

        if (property_exists($this, 'country_code') && $this->country_code !== null) {
            $value = strtoupper(trim((string) $this->country_code));
            $this->country_code = $value !== '' ? $value : null;
        }

        $this->parent_id = !empty($this->parent_id) ? (int) $this->parent_id : null;

        return parent::check();
    }
}
