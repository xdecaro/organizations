<?php

namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use xdecaro\Component\Organizations\Administrator\Service\OrganizationBodyDomain;

final class OrganizationBodiesModel extends ListModel
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
                'b.*',
                'p.name AS parent_name',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_bodies', 'b'))
            ->leftJoin($db->quoteName('#__xdecaroorganizations_bodies', 'p') . ' ON p.id = b.parent_id')
            ->where($db->quoteName('b.state') . ' >= 0')
            ->order($db->quoteName('b.body_type') . ' ASC, ' . $db->quoteName('b.name') . ' ASC');

        if ($this->organizationId < 1) {
            return $query->where('1 = 0');
        }

        $query->where($db->quoteName('b.organization_id') . ' = :organizationId')
            ->bind(':organizationId', $this->organizationId, ParameterType::INTEGER);

        return $query;
    }

    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $item) {
            $item->visual_status = OrganizationBodyDomain::status($item);
            $item->type_label_key = OrganizationBodyDomain::typeLabelKey((string) $item->body_type);
        }

        return $items;
    }
}
