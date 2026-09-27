<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use RuntimeException;

final class BackupStorageService
{
    public function resolvePrivateDirectory(): string
    {
        $params = ComponentHelper::getParams('com_xdecaroorganizations');
        $configured = trim((string) $params->get('backup_storage_path', ''));
        $directory = $configured !== ''
            ? $configured
            : dirname(JPATH_ROOT) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'xdecaroorganizations' . DIRECTORY_SEPARATOR . 'backups';

        if (!$this->isAbsolutePath($directory)) throw new RuntimeException('Organizations backup storage path must be absolute.');
        $webRoot = realpath(JPATH_ROOT) ?: JPATH_ROOT;
        $clean = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory), DIRECTORY_SEPARATOR);
        if (!is_dir($clean) && !@mkdir($clean, 0700, true) && !is_dir($clean)) throw new RuntimeException('Organizations backup storage directory cannot be created.');
        $resolved = realpath($clean);
        if ($resolved === false) throw new RuntimeException('Organizations backup storage directory cannot be resolved.');
        $resolvedRoot = rtrim(realpath($webRoot) ?: $webRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $resolvedWithSlash = rtrim($resolved, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($resolvedWithSlash, $resolvedRoot)) throw new RuntimeException('Organizations backup storage must be outside the Joomla web root.');
        if (!is_writable($resolved)) throw new RuntimeException('Organizations backup storage directory is not writable.');
        return $resolved;
    }

    public function pathFor(string $uuid): string
    {
        $uuid = strtolower(trim($uuid));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) throw new RuntimeException('Invalid backup UUID.');
        return $this->resolvePrivateDirectory() . DIRECTORY_SEPARATOR . 'organizations-' . $uuid . '.zip';
    }

    public function isHealthy(): array
    {
        try { $directory = $this->resolvePrivateDirectory(); return ['ok' => true, 'message' => 'OK', 'directory' => $directory]; }
        catch (\Throwable $e) { return ['ok' => false, 'message' => $e->getMessage(), 'directory' => null]; }
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
