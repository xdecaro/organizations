<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class DatabaseMaintenanceService
{
    private const FUNCTIONAL_TABLES = [
        '#__xdecaroorganizations_organizations',
        '#__xdecaroorganizations_bodies',
        '#__xdecaroorganizations_appointments',
        '#__xdecaroorganizations_delegations',
        '#__xdecaroorganizations_affiliations',
    ];

    private const DELETE_ORDER = [
        '#__xdecaroorganizations_delegations',
        '#__xdecaroorganizations_appointments',
        '#__xdecaroorganizations_bodies',
        '#__xdecaroorganizations_affiliations',
        '#__xdecaroorganizations_organizations',
    ];

    public function __construct(
        private DatabaseInterface $db,
        private DatabaseSchemaDefinition $definition,
        private DatabaseSchemaInspector $inspector,
        private BackupService $backup,
        private MaintenanceLogService $log
    ) {}

    public function inspect(): array
    {
        return $this->inspector->inspect();
    }

    public function check(int $actorUserId, bool $audit = false): array
    {
        $result = $this->inspector->inspect();
        if ($audit) $this->safeLog('database_check', $actorUserId, ['ok' => !empty($result['ok']), 'status' => (string) ($result['status'] ?? '')]);
        return $result;
    }

    public function repair(int $actorUserId): array
    {
        $before = $this->inspector->inspect();
        $operations = [];

        foreach ($this->definition->tables() as $table => $spec) {
            if (in_array($table, (array) ($before['missing_tables'] ?? []), true)) {
                $this->db->setQuery($this->definition->createTableSql($table))->execute();
                $operations[] = 'create_table:' . $table;
                continue;
            }
            foreach ((array) (($before['missing_columns'][$table] ?? [])) as $column) {
                $fragment = (string) ($spec['columns'][$column] ?? '');
                if ($fragment === '') continue;
                $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' ADD COLUMN ' . $fragment)->execute();
                $operations[] = 'add_column:' . $table . ':' . $column;
            }
            foreach ((array) (($before['incompatible_columns'][$table] ?? [])) as $column) {
                $fragment = (string) ($spec['columns'][$column] ?? '');
                if ($fragment === '') continue;
                $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' MODIFY COLUMN ' . $fragment)->execute();
                $operations[] = 'modify_column:' . $table . ':' . $column;
            }
            $expectedIndexes = array_merge((array) ($spec['unique_indexes'] ?? []), (array) ($spec['indexes'] ?? []));
            foreach ((array) (($before['missing_indexes'][$table] ?? [])) as $index) {
                $fragment = (string) ($expectedIndexes[$index] ?? '');
                if ($fragment === '') continue;
                $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' ADD ' . $fragment)->execute();
                $operations[] = 'add_index:' . $table . ':' . $index;
            }
            foreach ((array) (($before['incompatible_indexes'][$table] ?? [])) as $index) {
                $fragment = (string) ($expectedIndexes[$index] ?? '');
                if ($fragment === '') continue;
                $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' DROP INDEX ' . $this->db->quoteName($index))->execute();
                $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' ADD ' . $fragment)->execute();
                $operations[] = 'replace_index:' . $table . ':' . $index;
            }
            if (isset($before['engine_differences'][$table])) {
                $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' ENGINE=InnoDB')->execute();
                $operations[] = 'engine:' . $table;
            }
            if (isset($before['collation_differences'][$table])) {
                $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')->execute();
                $operations[] = 'collation:' . $table;
            }
        }

        $after = $this->inspector->inspect();
        $this->safeLog('database_repair', $actorUserId, ['before_ok' => !empty($before['ok']), 'after_ok' => !empty($after['ok']), 'operations' => $operations]);
        return ['before' => $before, 'after' => $after, 'operations' => $operations];
    }

    public function emptyFunctionalData(int $actorUserId, string $confirmation): array
    {
        if ($confirmation !== 'SVUOTA') throw new RuntimeException('Digita esattamente SVUOTA per confermare.', 400);
        $safety = $this->backup->create($actorUserId, 'before_empty_database');
        $this->backup->verify((string) ($safety['uuid'] ?? ''));
        $removed = [];
        $this->db->transactionStart();
        try {
            foreach (self::DELETE_ORDER as $table) {
                $removed[$table] = $this->countRows($table);
                $query = $this->db->getQuery(true)->delete($this->db->quoteName($table));
                $this->db->setQuery($query)->execute();
            }
            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw new RuntimeException('Organizations database empty failed and was rolled back.', 0, $e);
        }
        foreach (self::FUNCTIONAL_TABLES as $table) $this->resetAutoIncrement($table);
        $after = $this->inspector->inspect();
        $this->safeLog('database_empty', $actorUserId, ['safety_backup_uuid' => (string) ($safety['uuid'] ?? ''), 'safety_backup_filename' => (string) ($safety['filename'] ?? ''), 'removed' => $removed, 'schema_ok' => !empty($after['ok'])]);
        return ['safety_backup_uuid' => (string) ($safety['uuid'] ?? ''), 'safety_backup_filename' => (string) ($safety['filename'] ?? ''), 'removed' => $removed, 'after' => $after];
    }

    public function recreateFunctionalDatabase(int $actorUserId, string $confirmation): array
    {
        if ($confirmation !== 'RICREA') throw new RuntimeException('Digita esattamente RICREA per confermare.', 400);
        $safety = $this->backup->create($actorUserId, 'before_recreate_database');
        $this->backup->verify((string) ($safety['uuid'] ?? ''));

        foreach (self::DELETE_ORDER as $table) {
            if (!in_array($table, self::FUNCTIONAL_TABLES, true)) throw new RuntimeException('Unsafe Organizations recreate table whitelist.');
            $this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->quoteName($table))->execute();
        }
        foreach (self::FUNCTIONAL_TABLES as $table) {
            $this->db->setQuery($this->definition->createTableSql($table))->execute();
        }
        $after = $this->inspector->inspect();
        $this->safeLog('database_recreate', $actorUserId, ['safety_backup_uuid' => (string) ($safety['uuid'] ?? ''), 'safety_backup_filename' => (string) ($safety['filename'] ?? ''), 'schema_ok' => !empty($after['ok'])]);
        return ['safety_backup_uuid' => (string) ($safety['uuid'] ?? ''), 'safety_backup_filename' => (string) ($safety['filename'] ?? ''), 'after' => $after];
    }

    private function countRows(string $table): int
    {
        if (!in_array($table, self::FUNCTIONAL_TABLES, true)) throw new RuntimeException('Unsafe Organizations functional table.');
        return (int) $this->db->setQuery($this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName($table)))->loadResult();
    }

    private function resetAutoIncrement(string $table): void
    {
        if (!in_array($table, self::FUNCTIONAL_TABLES, true)) return;
        try { $this->db->setQuery('ALTER TABLE ' . $this->db->quoteName($table) . ' AUTO_INCREMENT = 1')->execute(); } catch (\Throwable) {}
    }

    private function safeLog(string $action, int $actorUserId, array $metadata): void
    {
        try { $this->log->log($action, null, $actorUserId, $metadata); } catch (\Throwable) {}
    }
}
