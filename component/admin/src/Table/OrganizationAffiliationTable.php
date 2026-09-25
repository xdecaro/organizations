<?php

namespace xdecaro\Component\Organizations\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

final class OrganizationAffiliationTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__xdecaroorganizations_affiliations', 'id', $db);
    }

    public function check(): bool
    {
        if ((int) $this->organization_id < 1 || (int) $this->target_organization_id < 1) {
            $this->setError('Organization and affiliated organization are required.');
            return false;
        }

        if ((int) $this->organization_id === (int) $this->target_organization_id) {
            $this->setError('An organization cannot be affiliated to itself.');
            return false;
        }

        return parent::check();
    }
}
