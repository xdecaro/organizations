<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use JsonException;

final class MaintenanceLogService
{
    public function __construct(private DatabaseInterface $db) {}

    public function log(string $action, ?string $subjectUuid, int $actorUserId, array $metadata = []): void
    {
        $action = trim($action);
        if ($action === '') throw new \InvalidArgumentException('Maintenance action is required.');
        $record = (object) [
            'action' => $action,
            'subject_uuid' => $subjectUuid !== null && trim($subjectUuid) !== '' ? strtolower(trim($subjectUuid)) : null,
            'actor_user_id' => max(0, $actorUserId),
            'created' => Factory::getDate()->toSql(),
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ];
        $this->db->insertObject('#__xdecaroorganizations_maintenance_log', $record, 'id');
    }

    public function recent(int $limit = 30, int $offset = 0, array $filters = []): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('l.id', 'id'),
                $this->db->quoteName('l.action', 'action'),
                $this->db->quoteName('l.subject_uuid', 'subject_uuid'),
                $this->db->quoteName('l.actor_user_id', 'actor_user_id'),
                $this->db->quoteName('l.created', 'created'),
                $this->db->quoteName('l.metadata', 'metadata'),
                $this->db->quoteName('u.name', 'actor_name'),
                $this->db->quoteName('u.username', 'actor_username'),
            ])
            ->from($this->db->quoteName('#__xdecaroorganizations_maintenance_log', 'l'))
            ->leftJoin(
                $this->db->quoteName('#__users', 'u')
                . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('l.actor_user_id')
            )
            ->order($this->db->quoteName('l.id') . ' DESC');

        $this->applyFilters($query, $filters);
        $rows = (array) $this->db->setQuery($query, $offset, $limit)->loadAssocList();
        foreach ($rows as &$row) $row['metadata'] = $this->decodeMetadata($row['metadata'] ?? null);
        unset($row);

        return array_values($rows);
    }

    public function countFiltered(array $filters = []): int
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__xdecaroorganizations_maintenance_log', 'l'));
        $this->applyFilters($query, $filters);

        return (int) $this->db->setQuery($query)->loadResult();
    }

    public function filterOptions(): array
    {
        $actionsQuery = $this->db->getQuery(true)
            ->select('DISTINCT ' . $this->db->quoteName('action'))
            ->from($this->db->quoteName('#__xdecaroorganizations_maintenance_log'))
            ->where($this->db->quoteName('action') . " <> ''")
            ->order($this->db->quoteName('action') . ' ASC');
        $actions = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) $this->db->setQuery($actionsQuery)->loadColumn()
        )));

        $usersQuery = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('l.actor_user_id', 'id'),
                'MAX(' . $this->db->quoteName('u.name') . ') AS ' . $this->db->quoteName('name'),
                'MAX(' . $this->db->quoteName('u.username') . ') AS ' . $this->db->quoteName('username'),
            ])
            ->from($this->db->quoteName('#__xdecaroorganizations_maintenance_log', 'l'))
            ->leftJoin(
                $this->db->quoteName('#__users', 'u')
                . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('l.actor_user_id')
            )
            ->where($this->db->quoteName('l.actor_user_id') . ' > 0')
            ->group($this->db->quoteName('l.actor_user_id'))
            ->order($this->db->quoteName('l.actor_user_id') . ' ASC');
        $users = array_values((array) $this->db->setQuery($usersQuery)->loadAssocList());

        return ['actions' => $actions, 'users' => $users];
    }

    private function applyFilters($query, array $filters): void
    {
        $action = substr(trim((string) ($filters['activity_action'] ?? $filters['action'] ?? '')), 0, 64);
        if ($action !== '') {
            $query->where($this->db->quoteName('l.action') . ' = :activity_action')
                ->bind(':activity_action', $action);
        }

        $actorUserId = max(0, (int) ($filters['activity_user'] ?? $filters['user'] ?? 0));
        if ($actorUserId > 0) {
            $query->where($this->db->quoteName('l.actor_user_id') . ' = :activity_user')
                ->bind(':activity_user', $actorUserId);
        }
    }

    private function decodeMetadata(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') return [];
        try { $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR); return is_array($decoded) ? $decoded : []; }
        catch (JsonException) { return []; }
    }
}
