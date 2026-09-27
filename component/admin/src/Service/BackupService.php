<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use JsonException;
use RuntimeException;
use ZipArchive;

final class BackupService
{
    private const FORMAT = 'xdecaro.organizations.backup';
    private const FORMAT_VERSION = 1;
    private const SCHEMA_VERSION = '1.2.17';
    private const PAYLOAD_TABLES = DatabaseSchemaDefinition::FUNCTIONAL_TABLES;
    private const ZIP_ENTRIES = ['manifest.json', 'data.json', 'SHA256SUMS.txt'];

    public function __construct(private DatabaseInterface $db, private BackupStorageService $storage, private MaintenanceLogService $log) {}

    public function create(int $actorUserId, string $reason = 'manual'): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('PHP ZIP extension is required to create Organizations backups.');
        $uuid = self::uuidV4();
        $path = $this->storage->pathFor($uuid);
        $tables = []; $counts = [];
        foreach (self::PAYLOAD_TABLES as $table) {
            $rows = $this->loadTableRows($table);
            $tables[$table] = $rows;
            $counts[$table] = count($rows);
        }
        $data = ['format' => self::FORMAT, 'format_version' => self::FORMAT_VERSION, 'tables' => $tables];
        $dataJson = $this->encodeJson($data);
        $payloadSha = hash('sha256', $dataJson);
        $componentVersion = $this->componentVersion();
        $createdUtc = gmdate('c');
        $reason = trim($reason) !== '' ? trim($reason) : 'manual';
        $manifest = [
            'format' => self::FORMAT,
            'format_version' => self::FORMAT_VERSION,
            'backup_uuid' => $uuid,
            'component_version' => $componentVersion,
            'schema_version' => self::SCHEMA_VERSION,
            'joomla_version' => JVERSION,
            'created_utc' => $createdUtc,
            'created_by' => max(0, $actorUserId),
            'reason' => $reason,
            'tables' => self::PAYLOAD_TABLES,
            'table_counts' => $counts,
            'payload_sha256' => $payloadSha,
        ];
        $manifestJson = $this->encodeJson($manifest);
        $sums = $payloadSha . "  data.json\n" . hash('sha256', $manifestJson) . "  manifest.json\n";
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Organizations backup ZIP cannot be created.');
        try {
            if (!$zip->addFromString('manifest.json', $manifestJson) || !$zip->addFromString('data.json', $dataJson) || !$zip->addFromString('SHA256SUMS.txt', $sums)) {
                throw new RuntimeException('Organizations backup ZIP could not be populated.');
            }
        } finally { $zip->close(); }
        clearstatcache(true, $path);
        $size = filesize($path); $fileSha = hash_file('sha256', $path);
        if ($size === false || $fileSha === false) { @unlink($path); throw new RuntimeException('Organizations backup metadata cannot be calculated.'); }
        $filename = $this->buildReadableFilename($reason, $componentVersion, (int) ($counts['#__xdecaroorganizations_organizations'] ?? 0), $createdUtc);
        $record = (object) [
            'uuid' => $uuid, 'filename' => $filename, 'storage_path' => $path, 'sha256' => $fileSha, 'size_bytes' => (int) $size,
            'organizations_count' => (int) ($counts['#__xdecaroorganizations_organizations'] ?? 0),
            'bodies_count' => (int) ($counts['#__xdecaroorganizations_bodies'] ?? 0),
            'appointments_count' => (int) ($counts['#__xdecaroorganizations_appointments'] ?? 0),
            'delegations_count' => (int) ($counts['#__xdecaroorganizations_delegations'] ?? 0),
            'affiliations_count' => (int) ($counts['#__xdecaroorganizations_affiliations'] ?? 0),
            'component_version' => $componentVersion, 'schema_version' => self::SCHEMA_VERSION,
            'created' => Factory::getDate()->toSql(), 'created_by' => max(0, $actorUserId), 'status' => 'ready',
        ];
        try { $this->db->insertObject('#__xdecaroorganizations_backups', $record, 'id'); }
        catch (\Throwable $e) { @unlink($path); throw $e; }
        $this->log->log('backup_create', $uuid, $actorUserId, ['filename' => $filename, 'counts' => $counts, 'size_bytes' => (int) $size, 'reason' => $reason]);
        return ['id' => (int) ($record->id ?? 0), 'uuid' => $uuid, 'filename' => $filename, 'path' => $path, 'sha256' => $fileSha, 'payload_sha256' => $payloadSha, 'size_bytes' => (int) $size, 'counts' => $counts, 'component_version' => $componentVersion, 'schema_version' => self::SCHEMA_VERSION, 'created_utc' => $createdUtc, 'status' => 'ready'];
    }

    public function list(): array
    {
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__xdecaroorganizations_backups'))->order($this->db->quoteName('id') . ' DESC');
        return array_values((array) $this->db->setQuery($query)->loadAssocList());
    }

    public function verify(string $uuid): array
    {
        $normalized = strtolower(trim($uuid));
        $row = $this->loadBackup($normalized);
        if (!$row || ($row['status'] ?? '') !== 'ready') throw new RuntimeException('Organizations backup is not available.', 404);
        $path = $this->storage->pathFor($normalized);
        if ((string) ($row['storage_path'] ?? '') !== $path) throw new RuntimeException('Organizations backup storage metadata is inconsistent.', 409);
        if (!is_file($path) || !is_readable($path)) throw new RuntimeException('Organizations backup file is missing.', 404);
        $sha = hash_file('sha256', $path);
        if ($sha === false || !hash_equals((string) $row['sha256'], $sha)) throw new RuntimeException('Organizations backup file integrity check failed.', 409);
        $archive = $this->verifyArchiveContents($path, $normalized);
        $row['path'] = $path;
        $row['payload_sha256'] = (string) ($archive['payload_sha256'] ?? '');
        $row['manifest'] = (array) ($archive['manifest'] ?? []);
        unset($row['storage_path']);
        return $row;
    }

    public function resolveDownload(string $uuid, int $actorUserId): array
    {
        $row = $this->verify($uuid);
        $this->log->log('backup_download', (string) $row['uuid'], $actorUserId, ['filename' => (string) ($row['filename'] ?? ''), 'size_bytes' => (int) ($row['size_bytes'] ?? 0)]);
        return $row;
    }

    public function delete(string $uuid, int $actorUserId): void
    {
        $normalized = strtolower(trim($uuid));
        $row = $this->loadBackup($normalized);
        if (!$row) throw new RuntimeException('Organizations backup not found.', 404);
        $path = $this->storage->pathFor($normalized);
        if ((string) ($row['storage_path'] ?? '') !== $path) throw new RuntimeException('Organizations backup storage metadata is inconsistent.', 409);
        if (is_file($path) && !@unlink($path)) throw new RuntimeException('Organizations backup file cannot be deleted.');
        $query = $this->db->getQuery(true)->delete($this->db->quoteName('#__xdecaroorganizations_backups'))->where($this->db->quoteName('uuid') . ' = :uuid')->bind(':uuid', $normalized);
        $this->db->setQuery($query)->execute();
        $this->log->log('backup_delete', $normalized, $actorUserId, ['filename' => (string) ($row['filename'] ?? '')]);
    }

    private function verifyArchiveContents(string $path, string $expectedUuid): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('PHP ZIP extension is required to verify Organizations backups.');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Organizations backup ZIP is invalid.', 409);
        try {
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if ($name === '' || str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) throw new RuntimeException('Unsafe path found in Organizations backup ZIP.', 409);
                $names[] = $name;
            }
            sort($names); $expectedEntries = self::ZIP_ENTRIES; sort($expectedEntries);
            if ($names !== $expectedEntries) throw new RuntimeException('Organizations backup ZIP contains missing or unexpected files.', 409);
            $manifestJson = $zip->getFromName('manifest.json');
            $dataJson = $zip->getFromName('data.json');
            $sums = $zip->getFromName('SHA256SUMS.txt');
            if (!is_string($manifestJson) || !is_string($dataJson) || !is_string($sums)) throw new RuntimeException('Organizations backup ZIP is incomplete.', 409);
        } finally { $zip->close(); }

        try {
            $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
            $data = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) { throw new RuntimeException('Organizations backup JSON is invalid.', 409, $e); }
        if (!is_array($manifest) || !is_array($data)) throw new RuntimeException('Organizations backup JSON structure is invalid.', 409);
        if (($manifest['format'] ?? null) !== self::FORMAT || ($data['format'] ?? null) !== self::FORMAT) throw new RuntimeException('Unsupported Organizations backup format.', 409);
        if ((int) ($manifest['format_version'] ?? 0) !== self::FORMAT_VERSION || (int) ($data['format_version'] ?? 0) !== self::FORMAT_VERSION) throw new RuntimeException('Unsupported Organizations backup format version.', 409);
        if (strtolower(trim((string) ($manifest['backup_uuid'] ?? ''))) !== $expectedUuid) throw new RuntimeException('Organizations backup UUID does not match its metadata record.', 409);

        $tables = $data['tables'] ?? null;
        if (!is_array($tables)) throw new RuntimeException('Organizations backup tables payload is invalid.', 409);
        $actualTables = array_keys($tables); sort($actualTables); $expectedTables = self::PAYLOAD_TABLES; sort($expectedTables);
        if ($actualTables !== $expectedTables) throw new RuntimeException('Organizations backup table whitelist mismatch.', 409);

        $payloadSha = hash('sha256', $dataJson);
        $manifestSha = hash('sha256', $manifestJson);
        if (!isset($manifest['payload_sha256']) || !is_string($manifest['payload_sha256']) || !hash_equals($manifest['payload_sha256'], $payloadSha)) throw new RuntimeException('Organizations backup payload checksum mismatch.', 409);
        if (!str_contains($sums, $payloadSha . '  data.json') || !str_contains($sums, $manifestSha . '  manifest.json')) throw new RuntimeException('Organizations backup checksum file is inconsistent.', 409);

        foreach (self::PAYLOAD_TABLES as $table) {
            if (!is_array($tables[$table])) throw new RuntimeException('Organizations backup table rows are invalid.', 409);
            $expectedCount = (int) (($manifest['table_counts'][$table] ?? -1));
            if ($expectedCount !== count($tables[$table])) throw new RuntimeException('Organizations backup table count mismatch.', 409);
        }
        return ['manifest' => $manifest, 'payload_sha256' => $payloadSha];
    }

    private function loadTableRows(string $table): array
    {
        if (!in_array($table, self::PAYLOAD_TABLES, true)) throw new RuntimeException('Table is not part of the Organizations backup whitelist.');
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->order($this->db->quoteName('id') . ' ASC');
        return array_values((array) $this->db->setQuery($query)->loadAssocList());
    }

    private function loadBackup(string $uuid): ?array
    {
        $uuid = strtolower(trim($uuid));
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__xdecaroorganizations_backups'))->where($this->db->quoteName('uuid') . ' = :uuid')->bind(':uuid', $uuid);
        return $this->db->setQuery($query, 0, 1)->loadAssoc() ?: null;
    }

    private function componentVersion(): string
    {
        $record = ExtensionHelper::getExtensionRecord('com_xdecaroorganizations', 'component', 1);
        $manifest = new Registry($record->manifest_cache ?? '{}');
        return (string) $manifest->get('version', self::SCHEMA_VERSION);
    }

    private function buildReadableFilename(string $reason, string $version, int $organizationCount, string $createdUtc): string
    {
        $slug = match ($reason) { 'manual' => 'manuale', 'pre-restore' => 'pre-restore', 'before_empty_database' => 'pre-svuota', 'before_recreate_database' => 'pre-ricrea', default => trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($reason)), '-') };
        if ($slug === '') $slug = 'automatico';
        try {
            $date = new \DateTimeImmutable($createdUtc); $app = Factory::getApplication(); $identity = $app->getIdentity();
            $tz = trim((string) $identity->getParam('timezone', '')) ?: (string) $app->get('offset', 'UTC');
            $stamp = $date->setTimezone(new \DateTimeZone($tz ?: 'UTC'))->format('Y-m-d_H-i-s');
        } catch (\Throwable) { $stamp = gmdate('Y-m-d_H-i-s'); }
        return sprintf('organizations-backup-%s-%s-v%s-%d-organizzazioni.zip', $slug, $stamp, preg_replace('/[^0-9A-Za-z._-]+/', '-', $version), max(0, $organizationCount));
    }

    private function encodeJson(array $value): string
    {
        try { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); }
        catch (JsonException $e) { throw new RuntimeException('Organizations backup JSON encoding failed.', 0, $e); }
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16); $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data); return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20,12);
    }
}
