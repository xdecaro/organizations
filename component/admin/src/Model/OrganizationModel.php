<?php
namespace xdecaro\Component\Organizations\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;

final class OrganizationModel extends AdminModel
{
    protected $text_prefix = 'COM_XDECAROORGANIZATIONS';

    public function getTable($type = 'Organization', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_xdecaroorganizations.organization',
            'organization',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (!$form) {
            return false;
        }

        $app = Factory::getApplication();
        $currentId = 0;

        if (is_array($data)) {
            $currentId = (int) ($data['id'] ?? 0);
        } elseif (is_object($data)) {
            $currentId = (int) ($data->id ?? 0);
        }

        if ($currentId < 1) {
            $currentId = $app->getInput()->getInt('id');
        }

        if ($currentId > 0) {
            $descendantIds = $this->getDescendantIds($currentId);
            $parentQuery = 'SELECT id AS value, name AS text FROM #__xdecaroorganizations_organizations'
                . ' WHERE state >= 0 AND id <> ' . $currentId;

            if ($descendantIds !== []) {
                $parentQuery .= ' AND id NOT IN (' . implode(',', $descendantIds) . ')';
            }

            $parentQuery .= ' ORDER BY name';
            $form->setFieldAttribute('parent_id', 'query', $parentQuery);
        }

        $user = $app->getIdentity();

        if (
            !$user->authorise('organizations.view_sensitive', 'com_xdecaroorganizations')
            && !$user->authorise('core.admin', 'com_xdecaroorganizations')
        ) {
            foreach (
                ['vat_id', 'tax_identifier', 'pec_email', 'address_line', 'postal_code', 'city', 'region', 'country_code', 'notes']
                as $name
            ) {
                $form->removeField($name);
            }
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecaroorganizations.edit.organization.data', []);

        return $data ?: $this->getItem();
    }

    protected function prepareTable($table): void
    {
        $now = Factory::getDate()->toSql();
        $uid = (int) Factory::getApplication()->getIdentity()->id;

        if (empty($table->uuid)) {
            $table->uuid = self::uuidV4();
        }

        if (empty($table->id)) {
            $table->created = $now;
            $table->created_by = $uid;
        } else {
            $table->modified = $now;
            $table->modified_by = $uid;
        }
    }

    public function save($data): bool
    {
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['parent_id'] = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        if (!$this->validParent((int) ($data['id'] ?? 0), (int) ($data['parent_id'] ?? 0))) {
            return false;
        }

        return parent::save($data);
    }

    protected function canDelete($record): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_xdecaroorganizations');
    }

    protected function canEditState($record): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_xdecaroorganizations');
    }

    private function getDescendantIds(int $id): array
    {
        if ($id < 1) {
            return [];
        }

        $db = $this->getDatabase();
        $seen = [$id => true];
        $frontier = [$id];
        $descendants = [];

        for ($depth = 0; $depth < 100 && $frontier !== []; $depth++) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__xdecaroorganizations_organizations'))
                ->where($db->quoteName('parent_id') . ' IN (' . implode(',', array_map('intval', $frontier)) . ')');

            $children = array_map('intval', (array) $db->setQuery($query)->loadColumn());
            $next = [];

            foreach ($children as $childId) {
                if ($childId < 1 || isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $descendants[] = $childId;
                $next[] = $childId;
            }

            $frontier = $next;
        }

        return $descendants;
    }

    private function validParent(int $id, int $parent): bool
    {
        if ($parent < 1) {
            return true;
        }

        if ($id > 0 && $id === $parent) {
            $this->setError('An organization cannot be its own parent.');
            return false;
        }

        $db = $this->getDatabase();
        $seen = [];
        $cursor = $parent;

        for ($i = 0; $i < 100 && $cursor > 0; $i++) {
            if (isset($seen[$cursor])) {
                $this->setError('Organization hierarchy contains a cycle.');
                return false;
            }

            $seen[$cursor] = true;

            if ($id > 0 && $cursor === $id) {
                $this->setError('Organization hierarchy would create a cycle.');
                return false;
            }

            $query = $db->getQuery(true)
                ->select($db->quoteName('parent_id'))
                ->from($db->quoteName('#__xdecaroorganizations_organizations'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $cursor, ParameterType::INTEGER);

            $cursor = (int) $db->setQuery($query, 0, 1)->loadResult();
        }

        if ($cursor > 0) {
            $this->setError('Organization hierarchy is too deep or invalid.');
            return false;
        }

        return true;
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
