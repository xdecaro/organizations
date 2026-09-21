<?php

namespace xdecaro\Component\Organizations\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class OrganizationTable extends Table
{
    private const STRUCTURE_LEVELS = [
        'unspecified',
        'international',
        'national',
        'regional',
        'provincial',
        'local',
        'branch',
        'other',
    ];

    private const TERRITORY_TYPES = [
        'international',
        'country',
        'region',
        'province',
        'metropolitan_city',
        'municipality',
        'custom',
    ];

    private const OPERATIONAL_STATUSES = [
        'active',
        'inactive',
        'represented',
        'commissaried',
        'merged',
        'dissolved',
    ];

    private const APPOINTMENT_MEMBERSHIP_REQUIREMENTS = [
        'inherit',
        'none',
        'active_member',
        'active_member_fee_current',
    ];

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

        foreach (
            [
                'legal_name',
                'code',
                'territory_name',
                'status_since',
                'vat_id',
                'tax_identifier',
                'email',
                'pec_email',
                'phone',
                'website',
                'address_line',
                'postal_code',
                'city',
                'region',
                'logo',
                'notes',
            ] as $field
        ) {
            if (property_exists($this, $field) && $this->{$field} !== null) {
                $value = trim((string) $this->{$field});
                $this->{$field} = $value !== '' ? $value : null;
            }
        }

        if (property_exists($this, 'country_code') && $this->country_code !== null) {
            $value = strtoupper(trim((string) $this->country_code));
            $this->country_code = $value !== '' ? $value : null;
        }

        if (property_exists($this, 'language')) {
            $language = trim((string) ($this->language ?? ''));
            $this->language = $language !== '' ? $language : '*';
        }

        $structureLevel = strtolower(trim((string) ($this->structure_level ?? 'unspecified')));
        $this->structure_level = in_array($structureLevel, self::STRUCTURE_LEVELS, true)
            ? $structureLevel
            : 'unspecified';

        $territoryType = strtolower(trim((string) ($this->territory_type ?? '')));
        $this->territory_type = in_array($territoryType, self::TERRITORY_TYPES, true)
            ? $territoryType
            : null;

        $operationalStatus = strtolower(trim((string) ($this->operational_status ?? 'active')));
        $this->operational_status = in_array($operationalStatus, self::OPERATIONAL_STATUSES, true)
            ? $operationalStatus
            : 'active';

        $membershipRequirement = strtolower(trim((string) ($this->appointment_membership_requirement ?? 'inherit')));
        $this->appointment_membership_requirement = in_array(
            $membershipRequirement,
            self::APPOINTMENT_MEMBERSHIP_REQUIREMENTS,
            true
        ) ? $membershipRequirement : 'inherit';

        foreach (
            [
                'autonomy_legal',
                'autonomy_management',
                'autonomy_administrative',
                'autonomy_tax',
                'autonomy_fiscal',
            ] as $field
        ) {
            if (property_exists($this, $field)) {
                $this->{$field} = (int) !empty($this->{$field});
            }
        }

        $this->parent_id = !empty($this->parent_id) ? (int) $this->parent_id : null;

        return parent::check();
    }
}
