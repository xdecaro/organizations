<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationAffiliationDomain;

final class OrganizationAffiliationsModel extends ListModel
{
    private int $organizationId = 0;
    private int $targetOrganizationId = 0;

    public function setOrganizationId(int $organizationId): void
    {
        $this->organizationId = max(0, $organizationId);
        $this->targetOrganizationId = 0;
    }

    public function setTargetOrganizationId(int $organizationId): void
    {
        $this->targetOrganizationId = max(0, $organizationId);
        $this->organizationId = 0;
    }

    protected function getStoreId($id = ''): string
    {
        return parent::getStoreId(
            $id . ':organization=' . $this->organizationId . ':target=' . $this->targetOrganizationId
        );
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
                's.name AS source_name',
                's.code AS source_code',
                's.type AS source_type',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_affiliations', 'a'))
            ->leftJoin($db->quoteName('#__xdecaroorganizations_organizations', 't') . ' ON t.id = a.target_organization_id')
            ->leftJoin($db->quoteName('#__xdecaroorganizations_organizations', 's') . ' ON s.id = a.organization_id')
            ->where($db->quoteName('a.state') . ' >= 0');

        if ($this->organizationId > 0) {
            return $query
                ->where($db->quoteName('a.organization_id') . ' = :organizationId')
                ->bind(':organizationId', $this->organizationId, ParameterType::INTEGER)
                ->order($db->quoteName('a.status') . ' ASC, ' . $db->quoteName('t.name') . ' ASC');
        }

        if ($this->targetOrganizationId > 0) {
            return $query
                ->where($db->quoteName('a.target_organization_id') . ' = :targetOrganizationId')
                ->bind(':targetOrganizationId', $this->targetOrganizationId, ParameterType::INTEGER)
                ->order($db->quoteName('a.status') . ' ASC, ' . $db->quoteName('s.name') . ' ASC');
        }

        return $query->where('1 = 0');
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
