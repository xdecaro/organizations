<?php
namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class DuplicateService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function find(int $limit = 100): array
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_xdecaroorganizations')
            && !$user->authorise('core.admin', 'com_xdecaroorganizations')) {
            throw new RuntimeException('Not authorised.', 403);
        }

        $limit = max(1, min(500, $limit));
        $out = [];

        foreach (['name', 'legal_name', 'code', 'email', 'pec_email'] as $field) {
            foreach ($this->findByField($field, 'LOWER', $limit) as $row) {
                $out[] = [
                    'type' => $field,
                    'key' => $row['duplicate_key'],
                    'count' => (int) $row['total'],
                ];
            }
        }

        if ($user->authorise('organizations.view_sensitive', 'com_xdecaroorganizations')
            || $user->authorise('core.admin', 'com_xdecaroorganizations')) {
            foreach (['vat_id', 'tax_identifier'] as $field) {
                foreach ($this->findByField($field, 'UPPER', $limit) as $row) {
                    $out[] = [
                        'type' => $field,
                        'key' => $row['duplicate_key'],
                        'count' => (int) $row['total'],
                    ];
                }
            }
        }

        usort($out, static fn(array $a, array $b): int => $b['count'] <=> $a['count'] ?: strcmp($a['type'], $b['type']));

        return array_slice($out, 0, $limit);
    }

    private function findByField(string $field, string $caseFunction, int $limit): array
    {
        $quotedField = $this->db->quoteName($field);
        $normalised = $caseFunction . '(TRIM(' . $quotedField . '))';

        $query = $this->db->getQuery(true)
            ->select([
                $normalised . ' AS duplicate_key',
                'COUNT(*) AS total',
            ])
            ->from($this->db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($this->db->quoteName('state') . ' >= 0')
            ->where($quotedField . ' IS NOT NULL')
            ->where($quotedField . " <> ''")
            ->group($normalised)
            ->having('COUNT(*) > 1')
            ->order('total DESC');

        return (array) $this->db->setQuery($query, 0, $limit)->loadAssocList();
    }
}
