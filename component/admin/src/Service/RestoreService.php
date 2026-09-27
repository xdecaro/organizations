<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use RuntimeException;
use ZipArchive;

final class RestoreService
{
    private const FORMAT = 'xdecaro.organizations.backup';
    private const FORMAT_VERSION = 1;
    private const PAYLOAD_TABLES = DatabaseSchemaDefinition::FUNCTIONAL_TABLES;
    private const ZIP_ENTRIES = ['manifest.json', 'data.json', 'SHA256SUMS.txt'];

    public function __construct(
        private DatabaseInterface $db,
        private DatabaseSchemaDefinition $definition,
        private BackupService $backup,
        private MaintenanceLogService $log
    ) {}

    public function preview(string $zipPath, int $actorUserId): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('PHP ZIP extension is required for Organizations restore.');
        if (!is_file($zipPath) || !is_readable($zipPath)) throw new RuntimeException('Organizations restore file is not readable.');
        $maxMb = max(1, (int) ComponentHelper::getParams('com_xdecaroorganizations')->get('backup_max_upload_mb', 64));
        $size = filesize($zipPath);
        if ($size === false || $size > $maxMb * 1024 * 1024) throw new RuntimeException('Organizations restore file exceeds the configured size limit.');

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) throw new RuntimeException('Invalid Organizations backup ZIP.');
        try {
            $names = []; $uncompressed = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if ($name === '' || str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) throw new RuntimeException('Unsafe path found in Organizations backup ZIP.');
                $stat = $zip->statIndex($i); $uncompressed += max(0, (int) ($stat['size'] ?? 0));
                $names[] = $name;
            }
            if ($uncompressed > $maxMb * 1024 * 1024 * 8) throw new RuntimeException('Organizations backup expanded payload is too large.');
            sort($names); $expected = self::ZIP_ENTRIES; sort($expected);
            if ($names !== $expected) throw new RuntimeException('Organizations backup ZIP contains missing or unexpected files.');
            $manifestJson = $zip->getFromName('manifest.json'); $dataJson = $zip->getFromName('data.json'); $sums = $zip->getFromName('SHA256SUMS.txt');
            if (!is_string($manifestJson) || !is_string($dataJson) || !is_string($sums)) throw new RuntimeException('Organizations backup ZIP is incomplete.');
        } finally { $zip->close(); }

        try {
            $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
            $data = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) { throw new RuntimeException('Organizations backup JSON is invalid.', 0, $e); }
        if (!is_array($manifest) || !is_array($data)) throw new RuntimeException('Organizations backup JSON structure is invalid.');
        $payloadHash = hash('sha256', $dataJson);
        if (!isset($manifest['payload_sha256']) || !is_string($manifest['payload_sha256']) || !hash_equals($manifest['payload_sha256'], $payloadHash)) throw new RuntimeException('Organizations backup payload checksum mismatch.');
        if (!str_contains($sums, $payloadHash . '  data.json') || !str_contains($sums, hash('sha256', $manifestJson) . '  manifest.json')) throw new RuntimeException('Organizations backup checksum file is inconsistent.');
        if (($manifest['format'] ?? null) !== self::FORMAT || ($data['format'] ?? null) !== self::FORMAT) throw new RuntimeException('Unsupported Organizations backup format.');
        if ((int) ($manifest['format_version'] ?? 0) !== self::FORMAT_VERSION || (int) ($data['format_version'] ?? 0) !== self::FORMAT_VERSION) throw new RuntimeException('Unsupported Organizations backup format version.');
        $tables = $data['tables'] ?? null;
        if (!is_array($tables)) throw new RuntimeException('Organizations backup tables payload is invalid.');
        $tableNames = array_keys($tables); sort($tableNames); $expectedTables = self::PAYLOAD_TABLES; sort($expectedTables);
        if ($tableNames !== $expectedTables) throw new RuntimeException('Organizations backup table whitelist mismatch.');

        $counts = [];
        foreach (self::PAYLOAD_TABLES as $table) {
            if (!is_array($tables[$table])) throw new RuntimeException('Organizations backup table rows are invalid.');
            $counts[$table] = count($tables[$table]);
            $this->validateTableRows($table, $tables[$table]);
        }
        $this->validateReferences($tables);
        $this->logSafely('restore_preview', (string) ($manifest['backup_uuid'] ?? ''), $actorUserId, ['component_version' => (string) ($manifest['component_version'] ?? ''), 'counts' => $counts, 'compatible' => true]);
        return ['compatible' => true, 'manifest' => $manifest, 'counts' => $counts, 'payload_sha256' => $payloadHash, 'data' => $data];
    }

    public function restoreFull(string $zipPath, int $actorUserId): array
    {
        $preview = $this->preview($zipPath, $actorUserId);
        $tables = $preview['data']['tables'] ?? null;
        if (!is_array($tables)) throw new RuntimeException('Organizations restore payload is unavailable.');
        $safety = $this->backup->create($actorUserId, 'pre-restore');
        $this->backup->verify((string) ($safety['uuid'] ?? ''));
        $counts = [];
        $this->db->transactionStart();
        try {
            foreach (['#__xdecaroorganizations_delegations','#__xdecaroorganizations_appointments','#__xdecaroorganizations_bodies','#__xdecaroorganizations_affiliations','#__xdecaroorganizations_organizations'] as $table) {
                $this->db->setQuery($this->db->getQuery(true)->delete($this->db->quoteName($table)))->execute();
            }

            $organizationParents = [];
            foreach ($tables['#__xdecaroorganizations_organizations'] as $row) {
                $organizationParents[(int) $row['id']] = $row['parent_id'] ?? null;
                $copy = $row; $copy['parent_id'] = null; $this->db->insertObject('#__xdecaroorganizations_organizations', (object) $copy);
            }
            foreach ($organizationParents as $id => $parentId) {
                if ($parentId === null || (int) $parentId === 0) continue;
                $this->db->updateObject('#__xdecaroorganizations_organizations', (object) ['id' => $id, 'parent_id' => (int) $parentId], 'id');
            }

            $bodyParents = [];
            foreach ($tables['#__xdecaroorganizations_bodies'] as $row) {
                $bodyParents[(int) $row['id']] = $row['parent_id'] ?? null;
                $copy = $row; $copy['parent_id'] = null; $this->db->insertObject('#__xdecaroorganizations_bodies', (object) $copy);
            }
            foreach ($bodyParents as $id => $parentId) {
                if ($parentId === null || (int) $parentId === 0) continue;
                $this->db->updateObject('#__xdecaroorganizations_bodies', (object) ['id' => $id, 'parent_id' => (int) $parentId], 'id');
            }

            foreach (['#__xdecaroorganizations_appointments','#__xdecaroorganizations_delegations','#__xdecaroorganizations_affiliations'] as $table) {
                foreach ($tables[$table] as $row) $this->db->insertObject($table, (object) $row);
            }
            foreach (self::PAYLOAD_TABLES as $table) {
                $expected = count($tables[$table]); $actual = (int) $this->db->setQuery($this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName($table)))->loadResult();
                if ($actual !== $expected) throw new RuntimeException('Organizations restore integrity count mismatch for ' . $table . '.');
                $counts[$table] = $actual;
            }
            $this->db->transactionCommit();
        } catch (\Throwable $e) {
            $this->db->transactionRollback();
            throw new RuntimeException('Organizations restore failed and was rolled back. Safety backup: ' . (string) ($safety['uuid'] ?? ''), 0, $e);
        }
        $this->logSafely('restore_full', (string) ($preview['manifest']['backup_uuid'] ?? ''), $actorUserId, ['safety_backup_uuid' => (string) ($safety['uuid'] ?? ''), 'safety_backup_filename' => (string) ($safety['filename'] ?? ''), 'restored_counts' => $counts]);
        return ['safety_backup_uuid' => (string) ($safety['uuid'] ?? ''), 'safety_backup_filename' => (string) ($safety['filename'] ?? ''), 'restored_counts' => $counts, 'integrity_ok' => true];
    }

    private function validateTableRows(string $table, array $rows): void
    {
        $spec = $this->definition->table($table);
        $allowedColumns = array_fill_keys(array_keys((array) ($spec['columns'] ?? [])), true);
        $seenIds = [];
        $seenUuids = [];
        foreach ($rows as $row) {
            if (!is_array($row)) throw new RuntimeException('Organizations backup contains an invalid table row.');
            foreach (array_keys($row) as $column) {
                if (!isset($allowedColumns[(string) $column])) throw new RuntimeException('Organizations backup contains an unexpected column in ' . $table . ': ' . $column);
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || isset($seenIds[$id])) throw new RuntimeException('Organizations backup contains invalid or duplicate IDs.');
            $seenIds[$id] = true;
            $uuid = strtolower(trim((string) ($row['uuid'] ?? '')));
            if (!$this->validUuid($uuid) || isset($seenUuids[$uuid])) throw new RuntimeException('Organizations backup contains invalid or duplicate entity UUIDs.');
            $seenUuids[$uuid] = true;
        }
    }

    private function validateReferences(array $tables): void
    {
        $orgIds = $this->idSet($tables['#__xdecaroorganizations_organizations']);
        foreach ($tables['#__xdecaroorganizations_organizations'] as $row) {
            $parent = (int) ($row['parent_id'] ?? 0); if ($parent > 0 && !isset($orgIds[$parent])) throw new RuntimeException('Organizations backup contains an invalid organization parent reference.');
        }
        $bodyIds = $this->idSet($tables['#__xdecaroorganizations_bodies']);
        foreach ($tables['#__xdecaroorganizations_bodies'] as $row) {
            if (!isset($orgIds[(int) ($row['organization_id'] ?? 0)])) throw new RuntimeException('Organizations backup contains an invalid body organization reference.');
            $parent = (int) ($row['parent_id'] ?? 0); if ($parent > 0 && !isset($bodyIds[$parent])) throw new RuntimeException('Organizations backup contains an invalid body parent reference.');
        }
        $appointmentIds = $this->idSet($tables['#__xdecaroorganizations_appointments']);
        foreach ($tables['#__xdecaroorganizations_appointments'] as $row) {
            if (!isset($orgIds[(int) ($row['organization_id'] ?? 0)])) throw new RuntimeException('Organizations backup contains an invalid appointment organization reference.');
            $body = (int) ($row['body_id'] ?? 0); if ($body > 0 && !isset($bodyIds[$body])) throw new RuntimeException('Organizations backup contains an invalid appointment body reference.');
        }
        foreach ($tables['#__xdecaroorganizations_delegations'] as $row) {
            if (!isset($orgIds[(int) ($row['organization_id'] ?? 0)]) || !isset($appointmentIds[(int) ($row['appointment_id'] ?? 0)])) throw new RuntimeException('Organizations backup contains an invalid delegation reference.');
        }
        foreach ($tables['#__xdecaroorganizations_affiliations'] as $row) {
            if (!isset($orgIds[(int) ($row['organization_id'] ?? 0)]) || !isset($orgIds[(int) ($row['target_organization_id'] ?? 0)])) throw new RuntimeException('Organizations backup contains an invalid affiliation reference.');
        }
    }

    private function idSet(array $rows): array
    {
        $set = [];
        foreach ($rows as $row) { $id = (int) ($row['id'] ?? 0); if ($id <= 0 || isset($set[$id])) throw new RuntimeException('Organizations backup contains invalid or duplicate IDs.'); $set[$id] = true; }
        return $set;
    }

    private function validUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', trim($uuid)) === 1;
    }

    private function logSafely(string $action, ?string $uuid, int $actorUserId, array $metadata): void
    {
        try { $this->log->log($action, $uuid, $actorUserId, $metadata); } catch (\Throwable) {}
    }
}
