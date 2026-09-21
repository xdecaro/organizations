<?php

namespace xdecaro\Component\Organizations\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;

final class OrganizationsModel extends ListModel
{
    protected function populateState($ordering = 'a.name', $direction = 'asc'): void
    {
        parent::populateState($ordering, $direction);

        $app = Factory::getApplication();
        $params = $app->getParams();

        $this->setState('filter.search', trim($app->getInput()->getString('search', '')));
        $this->setState('list.limit', max(1, min(100, (int) $params->get('list_limit', 12))));
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                'a.id',
                'a.name',
                'a.legal_name',
                'a.type',
                'a.structure_level',
                'a.territory_name',
                'a.email',
                'a.phone',
                'a.website',
                'a.logo',
                'a.parent_id',
                'a.language',
                'a.access',
            ])
            ->from($db->quoteName('#__xdecaroorganizations_organizations', 'a'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('a.operational_status') . ' = ' . $db->quote('active'));

        $levels = array_values(array_unique(array_map('intval', Factory::getApplication()->getIdentity()->getAuthorisedViewLevels())));
        if ($levels === []) {
            return $query->where('1 = 0');
        }

        $query->whereIn($db->quoteName('a.access'), $levels);

        $language = Factory::getApplication()->getLanguage()->getTag();
        $query->where(
            '(' . $db->quoteName('a.language') . ' IS NULL'
            . ' OR ' . $db->quoteName('a.language') . ' = ' . $db->quote('')
            . ' OR ' . $db->quoteName('a.language') . ' = ' . $db->quote('*')
            . ' OR ' . $db->quoteName('a.language') . ' = ' . $db->quote($language)
            . ')'
        );

        $search = trim((string) $this->getState('filter.search', ''));
        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(
                '(' . $db->quoteName('a.name') . ' LIKE :searchName'
                . ' OR ' . $db->quoteName('a.legal_name') . ' LIKE :searchLegal'
                . ' OR ' . $db->quoteName('a.territory_name') . ' LIKE :searchTerritory'
                . ')'
            )
                ->bind(':searchName', $like)
                ->bind(':searchLegal', $like)
                ->bind(':searchTerritory', $like);
        }

        return $query->order($db->quoteName('a.name') . ' ASC');
    }
}
