<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationAffiliationDomain;

final class OrganizationAffiliationsModel extends ListModel
{
    private int $organizationId = 0;

    public function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = max(0, $organizationId);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'a.*',
                't.name AS target_name',
                't.code AS target_code',
                't.type AS target_type',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_affiliations', 'a'))
            ->leftJoin($db->quoteName('#__xdecaroorganizations_organizations', 't') . ' ON t.id = a.target_organization_id')
            ->where($db->quoteName('a.state') . ' >= 0')
            ->order($db->quoteName('a.status') . ' ASC, ' . $db->quoteName('t.name') . ' ASC');

        if ($this->organizationId < 1) {
            return $query->where('1 = 0');
        }

        return $query
            ->where($db->quoteName('a.organization_id') . ' = :organizationId')
            ->bind(':organizationId', $this->organizationId, ParameterType::INTEGER);
    }

    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $item) {
            $item->type_label_key = OrganizationAffiliationDomain::typeLabelKey((string) ($item->relation_type ?? 'other'));
            $item->status_label_key = OrganizationAffiliationDomain::statusLabelKey((string) ($item->status ?? 'inactive'));
        }

        return $items;
    }
}
