<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeImmutable;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Throwable;

final class ImportService
{
    public const MAX_BATCH_SIZE = 150;
    private const TYPES = ['organization','association','club','federation','company','public_body','school','sponsor','supplier'];
    private const LEVELS = ['unspecified','international','national','regional','provincial','local','branch','other'];
    private const STATUSES = ['active','inactive','represented','commissaried','merged','dissolved'];

    private ?array $countryAliases = null;
    private ?array $existingRows = null;
    private ?array $affiliationTargets = null;

    public function __construct(private DatabaseInterface $db)
    {
    }

    public function analyzeRows(array $rows, string $defaultType = 'club'): array
    {
        $rows = array_slice(array_values($rows), 0, 5000);
        $defaultType = in_array($defaultType, self::TYPES, true) ? $defaultType : 'organization';

        $validRows = [];
        $invalid = [];

        foreach ($rows as $source) {
            if (!is_array($source)) {
                continue;
            }

            [$record, $errors, $warnings] = $this->normalizeRecord($source, $defaultType);
            $rowNumber = max(0, (int) ($source['_row'] ?? 0));

            if ($errors !== []) {
                $invalid[] = $this->resultRow($rowNumber, 'invalid', $errors[0], $warnings, $record);
                continue;
            }

            $record['_row'] = $rowNumber;
            $record['_warnings'] = $warnings;
            $validRows[] = $record;
        }

        $validCount = count($validRows);
        [$deduplicated, $duplicateRows, $duplicateGroups, $duplicateConflicts] = $this->consolidateFileDuplicates($validRows);
        $invalid = array_merge($invalid, $duplicateConflicts);

        $existing = [];
        $candidates = [];

        foreach ($deduplicated as $record) {
            $match = $this->matchExisting($record);

            if ($match['status'] === 'existing') {
                $existing[] = $this->resultRow(
                    (int) $record['_row'],
                    'existing',
                    'existing_organization',
                    (array) ($record['_warnings'] ?? []),
                    $record,
                    ['match' => $match['match'] ?? null]
                );
                continue;
            }

            if ($match['status'] === 'conflict') {
                $invalid[] = $this->resultRow(
                    (int) $record['_row'],
                    'invalid',
                    (string) ($match['message'] ?? 'possible_existing_organization'),
                    (array) ($record['_warnings'] ?? []),
                    $record,
                    ['match' => $match['match'] ?? null]
                );
                continue;
            }

            $affiliation = $this->resolveAffiliation($record);
            if ($affiliation['status'] === 'error') {
                $invalid[] = $this->resultRow(
                    (int) $record['_row'],
                    'invalid',
                    (string) $affiliation['message'],
                    (array) ($record['_warnings'] ?? []),
                    $record,
                    ['details' => (string) ($affiliation['details'] ?? '')]
                );
                continue;
            }

            if (isset($affiliation['target_id'])) {
                $record['_affiliation_target_id'] = (int) $affiliation['target_id'];
                $record['_affiliation_target_label'] = (string) ($affiliation['target_label'] ?? '');
            }

            $candidates[] = $record;
        }

        return [
            'summary' => [
                'total' => count($rows),
                'valid' => $validCount,
                'duplicate_groups' => $duplicateGroups,
                'invalid' => count($invalid),
                'existing' => count($existing),
                'new' => count($candidates),
            ],
            'candidates' => array_values($candidates),
            'invalid' => array_values($invalid),
            'existing' => array_values($existing),
            'duplicate_rows' => array_values($duplicateRows),
        ];
    }

    public function importBatch(array $rows, int $userId, string $defaultType = 'club'): array
    {
        $rows = array_slice(array_values($rows), 0, self::MAX_BATCH_SIZE);
        $defaultType = in_array($defaultType, self::TYPES, true) ? $defaultType : 'organization';

        $summary = [
            'inserted' => 0,
            'existing' => 0,
            'invalid' => 0,
            'errors' => 0,
            'results' => [],
        ];

        foreach ($rows as $source) {
            $source = is_array($source) ? $source : [];
            $rowNumber = max(0, (int) ($source['_row'] ?? 0));
            [$record, $errors, $warnings] = $this->normalizeRecord($source, $defaultType);

            if ($errors !== []) {
                $summary['invalid']++;
                $summary['results'][] = $this->resultRow($rowNumber, 'invalid', $errors[0], $warnings, $record);
                continue;
            }

            $record['_row'] = $rowNumber;
            $match = $this->matchExisting($record, true);

            if ($match['status'] === 'existing') {
                $summary['existing']++;
                $summary['results'][] = $this->resultRow($rowNumber, 'existing', 'existing_organization', $warnings, $record);
                continue;
            }

            if ($match['status'] === 'conflict') {
                $summary['invalid']++;
                $summary['results'][] = $this->resultRow(
                    $rowNumber,
                    'invalid',
                    (string) ($match['message'] ?? 'possible_existing_organization'),
                    $warnings,
                    $record
                );
                continue;
            }

            $affiliation = $this->resolveAffiliation($record, true);
            if ($affiliation['status'] === 'error') {
                $summary['invalid']++;
                $summary['results'][] = $this->resultRow(
                    $rowNumber,
                    'invalid',
                    (string) $affiliation['message'],
                    $warnings,
                    $record,
                    ['details' => (string) ($affiliation['details'] ?? '')]
                );
                continue;
            }

            $transaction = false;

            try {
                $this->db->transactionStart();
                $transaction = true;

                $organization = (object) [
                    'uuid' => self::uuidV4(),
                    'name' => $record['name'],
                    'legal_name' => $record['legal_name'],
                    'code' => $record['code'],
                    'type' => $record['type'],
                    'structure_level' => $record['structure_level'],
                    'territory_type' => null,
                    'territory_name' => null,
                    'operational_status' => $record['operational_status'],
                    'status_since' => null,
                    'autonomy_legal' => 0,
                    'autonomy_management' => 0,
                    'autonomy_administrative' => 0,
                    'autonomy_tax' => 0,
                    'autonomy_fiscal' => 0,
                    'appointment_membership_requirement' => 'inherit',
                    'parent_id' => null,
                    'vat_id' => $record['vat_id'],
                    'tax_identifier' => $record['tax_identifier'],
                    'email' => $record['email'],
                    'pec_email' => null,
                    'phone' => $record['phone'],
                    'website' => $record['website'],
                    'address_line' => $record['address_line'],
                    'postal_code' => $record['postal_code'],
                    'city' => $record['city'],
                    'province' => $record['province'],
                    'region' => $record['region'],
                    'country_code' => $record['country_code'],
                    'language' => '*',
                    'logo' => null,
                    'notes' => null,
                    'state' => 1,
                    'access' => 1,
                    'created' => Factory::getDate()->toSql(),
                    'created_by' => $userId,
                    'modified' => null,
                    'modified_by' => 0,
                ];

                $this->db->insertObject('#__xdecaroorganizations_organizations', $organization, 'id');
                $organizationId = (int) ($organization->id ?? 0);

                if ($organizationId < 1) {
                    throw new \RuntimeException('Imported organization did not return a database id.');
                }

                if (isset($affiliation['target_id'])) {
                    $this->insertAffiliation(
                        $organizationId,
                        (int) $affiliation['target_id'],
                        $record,
                        $userId
                    );
                }

                $this->db->transactionCommit();
                $transaction = false;
                $this->existingRows = null;

                $summary['inserted']++;
                $summary['results'][] = $this->resultRow(
                    $rowNumber,
                    'inserted',
                    'organization_imported',
                    $warnings,
                    $record,
                    ['uuid' => (string) $organization->uuid]
                );
            } catch (Throwable $exception) {
                if ($transaction) {
                    try {
                        $this->db->transactionRollback();
                    } catch (Throwable $rollbackException) {
                        Log::add('Organizations import rollback failed: ' . $rollbackException->getMessage(), Log::ERROR, 'com_xdecaroorganizations');
                    }
                }

                Log::add(
                    'Organizations import row ' . $rowNumber . ' failed: ' . $exception->getMessage(),
                    Log::ERROR,
                    'com_xdecaroorganizations'
                );

                $summary['errors']++;
                $summary['results'][] = $this->resultRow($rowNumber, 'error', 'database_error', $warnings, $record);
            }
        }

        return $summary;
    }

    private function normalizeRecord(array $row, string $defaultType): array
    {
        $errors = [];
        $warnings = [];

        $name = $this->cleanString($row['name'] ?? null, 255);
        if ($name === null) {
            $errors[] = 'missing_name';
        }

        $type = $this->normalizeType($row['type'] ?? null, $defaultType);
        if ($type === null) {
            $errors[] = 'invalid_type';
            $type = $defaultType;
        }

        $level = $this->normalizeLevel($row['structure_level'] ?? null);
        if ($level === null) {
            $errors[] = 'invalid_structure_level';
            $level = 'unspecified';
        }

        $operationalStatus = $this->normalizeOperationalStatus($row['operational_status'] ?? null);
        if ($operationalStatus === null) {
            $errors[] = 'invalid_operational_status';
            $operationalStatus = 'active';
        }

        [$countryCode, $countryError] = $this->normalizeCountry($row['country_code'] ?? null);
        if ($countryError !== null) {
            $errors[] = $countryError;
        }

        $email = strtolower(trim((string) ($row['email'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $warnings[] = 'invalid_email_removed';
            $email = '';
        }

        $website = trim((string) ($row['website'] ?? ''));
        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) {
            $warnings[] = 'invalid_website_removed';
            $website = '';
        }

        $affiliationType = $this->normalizeAffiliationType($row['affiliation_type'] ?? null);
        if ($affiliationType === null) {
            $errors[] = 'invalid_affiliation_type';
            $affiliationType = 'sports_affiliation';
        }

        $affiliationStatus = $this->normalizeAffiliationStatus($row['affiliation_status'] ?? null);
        if ($affiliationStatus === null) {
            $errors[] = 'invalid_affiliation_status';
            $affiliationStatus = 'active';
        }

        $affiliationStartRaw = trim((string) ($row['affiliation_starts_on'] ?? ''));
        $affiliationEndRaw = trim((string) ($row['affiliation_ends_on'] ?? ''));
        $affiliationStart = $this->normalizeDate($affiliationStartRaw);
        $affiliationEnd = $this->normalizeDate($affiliationEndRaw);

        if ($affiliationStartRaw !== '' && $affiliationStart === null) {
            $errors[] = 'invalid_affiliation_start';
        }
        if ($affiliationEndRaw !== '' && $affiliationEnd === null) {
            $errors[] = 'invalid_affiliation_end';
        }
        if ($affiliationStart !== null && $affiliationEnd !== null && $affiliationEnd < $affiliationStart) {
            $errors[] = 'invalid_affiliation_period';
        }

        $record = [
            'name' => $name ?? '',
            'legal_name' => $this->cleanString($row['legal_name'] ?? null, 255),
            'code' => $this->cleanString($row['code'] ?? null, 100),
            'type' => $type,
            'structure_level' => $level,
            'operational_status' => $operationalStatus,
            'vat_id' => $this->normalizeIdentifier($row['vat_id'] ?? null),
            'tax_identifier' => $this->normalizeIdentifier($row['tax_identifier'] ?? null),
            'email' => $email !== '' ? $email : null,
            'phone' => $this->normalizePhone($row['phone'] ?? null),
            'website' => $website !== '' ? $website : null,
            'address_line' => $this->cleanString($row['address_line'] ?? null, 255),
            'postal_code' => $this->cleanString($row['postal_code'] ?? null, 32),
            'city' => $this->cleanString($row['city'] ?? null, 190),
            'province' => $this->cleanString($row['province'] ?? null, 190),
            'region' => $this->cleanString($row['region'] ?? null, 190),
            'country_code' => $countryCode,
            'affiliation_target' => $this->cleanString($row['affiliation_target'] ?? null, 255),
            'affiliation_type' => $affiliationType,
            'affiliation_code' => $this->cleanString($row['affiliation_code'] ?? null, 100),
            'affiliation_status' => $affiliationStatus,
            'affiliation_starts_on' => $affiliationStart,
            'affiliation_ends_on' => $affiliationEnd,
        ];

        return [$record, array_values(array_unique($errors)), array_values(array_unique($warnings))];
    }

    private function consolidateFileDuplicates(array $rows): array
    {
        $groups = [];
        foreach ($rows as $record) {
            $key = $this->fileDuplicateKey($record);
            $groups[$key][] = $record;
        }

        $output = [];
        $duplicateRows = [];
        $conflicts = [];
        $duplicateGroups = 0;

        foreach ($groups as $items) {
            if (count($items) === 1) {
                $output[] = $items[0];
                continue;
            }

            $duplicateGroups++;
            $rowNumbers = array_values(array_map(static fn(array $item): int => (int) $item['_row'], $items));
            $fingerprints = array_values(array_unique(array_map(fn(array $item): string => $this->fingerprint($item), $items)));
            $isConflict = count($fingerprints) > 1;

            foreach ($items as $index => $item) {
                $duplicateRows[] = [
                    'row' => (int) $item['_row'],
                    'name' => (string) $item['name'],
                    'country_code' => (string) ($item['country_code'] ?? ''),
                    'code' => (string) ($item['code'] ?? ''),
                    'group_rows' => $rowNumbers,
                    'duplicate_status' => $isConflict ? 'conflict' : ($index === 0 ? 'primary' : 'consolidated'),
                ];
            }

            if ($isConflict) {
                foreach ($items as $item) {
                    $conflicts[] = $this->resultRow(
                        (int) $item['_row'],
                        'invalid',
                        'duplicate_conflict',
                        (array) ($item['_warnings'] ?? []),
                        $item
                    );
                }
                continue;
            }

            $output[] = $items[0];
        }

        return [$output, $duplicateRows, $duplicateGroups, $conflicts];
    }

    private function matchExisting(array $record, bool $refresh = false): array
    {
        if ($refresh) {
            $this->existingRows = null;
        }

        $rows = $this->existingOrganizations();
        $strong = [];

        foreach ($rows as $existing) {
            if ($record['vat_id'] !== null && $existing['vat_id'] !== null && $record['vat_id'] === $existing['vat_id']) {
                $strong[(int) $existing['id']] = $existing;
            }
            if ($record['tax_identifier'] !== null && $existing['tax_identifier'] !== null && $record['tax_identifier'] === $existing['tax_identifier']) {
                $strong[(int) $existing['id']] = $existing;
            }
            if ($record['code'] !== null && $record['country_code'] !== null
                && $existing['code'] !== null && $existing['country_code'] !== null
                && $this->fold($record['code']) === $this->fold($existing['code'])
                && $record['country_code'] === $existing['country_code']) {
                $strong[(int) $existing['id']] = $existing;
            }
        }

        if (count($strong) > 1) {
            return ['status' => 'conflict', 'message' => 'existing_identifier_conflict'];
        }

        if ($strong !== []) {
            $match = array_values($strong)[0];
            return $this->hasIdentifierContradiction($record, $match)
                ? ['status' => 'conflict', 'message' => 'existing_identifier_conflict', 'match' => $match]
                : ['status' => 'existing', 'match' => $match];
        }

        $sameName = array_values(array_filter($rows, fn(array $existing): bool => $this->fold($record['name']) === $this->fold($existing['name'])));

        if ($record['country_code'] !== null) {
            $sameCountry = array_values(array_filter(
                $sameName,
                static fn(array $existing): bool => (string) ($existing['country_code'] ?? '') === (string) $record['country_code']
            ));

            if (count($sameCountry) === 1) {
                $match = $sameCountry[0];
                return $this->hasIdentifierContradiction($record, $match)
                    ? ['status' => 'conflict', 'message' => 'existing_identifier_conflict', 'match' => $match]
                    : ['status' => 'existing', 'match' => $match];
            }

            if (count($sameCountry) > 1) {
                return ['status' => 'conflict', 'message' => 'possible_existing_organization'];
            }
        }

        if ($sameName !== []) {
            return ['status' => 'conflict', 'message' => 'possible_existing_organization', 'match' => $sameName[0] ?? null];
        }

        return ['status' => 'new'];
    }

    private function resolveAffiliation(array $record, bool $refresh = false): array
    {
        $target = trim((string) ($record['affiliation_target'] ?? ''));
        if ($target === '') {
            return ['status' => 'none'];
        }

        if ($refresh) {
            $this->affiliationTargets = null;
        }

        $needle = $this->fold($target);
        $matches = [];

        foreach ($this->availableAffiliationTargets() as $organization) {
            if ($organization['code'] !== null && $this->fold($organization['code']) === $needle) {
                $matches[(int) $organization['id']] = $organization;
            }
        }

        if ($matches === []) {
            foreach ($this->availableAffiliationTargets() as $organization) {
                if ($this->fold($organization['name']) === $needle
                    || ($organization['legal_name'] !== null && $this->fold($organization['legal_name']) === $needle)) {
                    $matches[(int) $organization['id']] = $organization;
                }
            }
        }

        if (count($matches) === 0) {
            return ['status' => 'error', 'message' => 'affiliation_target_not_found', 'details' => $target];
        }
        if (count($matches) > 1) {
            return ['status' => 'error', 'message' => 'affiliation_target_ambiguous', 'details' => $target];
        }

        $match = array_values($matches)[0];

        if ($this->fold((string) $record['name']) === $this->fold((string) $match['name'])) {
            return ['status' => 'error', 'message' => 'affiliation_target_self', 'details' => $target];
        }

        return [
            'status' => 'resolved',
            'target_id' => (int) $match['id'],
            'target_label' => (string) $match['name'],
        ];
    }

    private function insertAffiliation(int $organizationId, int $targetId, array $record, int $userId): void
    {
        $payload = [
            'organization_id' => $organizationId,
            'target_organization_id' => $targetId,
            'relation_type' => (string) $record['affiliation_type'],
            'relation_code' => $record['affiliation_code'],
            'starts_on' => $record['affiliation_starts_on'],
            'ends_on' => $record['affiliation_ends_on'],
            'status' => (string) $record['affiliation_status'],
            'state' => 1,
        ];

        $errors = OrganizationAffiliationDomain::validate($payload);
        if ($errors !== []) {
            throw new \RuntimeException(implode(' ', array_values($errors)));
        }

        $affiliation = (object) array_merge($payload, [
            'uuid' => self::uuidV4(),
            'notes' => null,
            'created' => Factory::getDate()->toSql(),
            'created_by' => $userId,
            'modified' => null,
            'modified_by' => 0,
        ]);

        $this->db->insertObject('#__xdecaroorganizations_affiliations', $affiliation, 'id');
    }

    private function existingOrganizations(): array
    {
        if ($this->existingRows !== null) {
            return $this->existingRows;
        }

        $query = $this->db->getQuery(true)
            ->select(['id','name','legal_name','code','vat_id','tax_identifier','country_code'])
            ->from($this->db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($this->db->quoteName('state') . ' >= 0');

        $rows = (array) $this->db->setQuery($query)->loadAssocList();

        foreach ($rows as &$row) {
            $row['vat_id'] = $this->normalizeIdentifier($row['vat_id'] ?? null);
            $row['tax_identifier'] = $this->normalizeIdentifier($row['tax_identifier'] ?? null);
            $row['code'] = $this->cleanString($row['code'] ?? null, 100);
            $row['country_code'] = strtoupper(trim((string) ($row['country_code'] ?? ''))) ?: null;
        }

        return $this->existingRows = $rows;
    }

    private function availableAffiliationTargets(): array
    {
        if ($this->affiliationTargets !== null) {
            return $this->affiliationTargets;
        }

        $query = $this->db->getQuery(true)
            ->select(['id','name','legal_name','code'])
            ->from($this->db->quoteName('#__xdecaroorganizations_organizations'))
            ->where($this->db->quoteName('state') . ' = 1')
            ->order($this->db->quoteName('name') . ' ASC');

        return $this->affiliationTargets = (array) $this->db->setQuery($query)->loadAssocList();
    }

    private function hasIdentifierContradiction(array $record, array $existing): bool
    {
        foreach (['vat_id','tax_identifier'] as $field) {
            if ($record[$field] !== null && $existing[$field] !== null && $record[$field] !== $existing[$field]) {
                return true;
            }
        }

        if ($record['code'] !== null && $existing['code'] !== null
            && $this->fold($record['code']) !== $this->fold($existing['code'])) {
            return true;
        }

        return false;
    }

    private function fileDuplicateKey(array $record): string
    {
        $name = $this->fold($record['name']);
        $country = (string) ($record['country_code'] ?? '');
        return $name . '|' . $country;
    }

    private function fingerprint(array $record): string
    {
        $copy = $record;
        unset($copy['_row'], $copy['_warnings'], $copy['_affiliation_target_id'], $copy['_affiliation_target_label']);
        ksort($copy);
        return hash('sha256', json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function resultRow(int $row, string $status, string $message, array $warnings, array $record, array $extra = []): array
    {
        return array_merge([
            'row' => $row,
            'status' => $status,
            'message' => $message,
            'warnings' => array_values($warnings),
            'name' => (string) ($record['name'] ?? ''),
            'country_code' => (string) ($record['country_code'] ?? ''),
            'code' => (string) ($record['code'] ?? ''),
            'affiliation_target' => (string) ($record['affiliation_target'] ?? ''),
        ], $extra);
    }

    private function normalizeType(mixed $value, string $default): ?string
    {
        $raw = $this->fold((string) $value);
        if ($raw === '') {
            return $default;
        }

        $aliases = [
            'organization' => ['organization','organizzazione'],
            'association' => ['association','associazione'],
            'club' => ['club','societa club','societa / club','società / club','societa sportiva','società sportiva','asd','asdc','a.s.d.','a.s.d.c.'],
            'federation' => ['federation','federazione'],
            'company' => ['company','impresa','azienda'],
            'public_body' => ['public body','ente pubblico'],
            'school' => ['school','scuola'],
            'sponsor' => ['sponsor'],
            'supplier' => ['supplier','fornitore'],
        ];

        foreach ($aliases as $type => $values) {
            if (in_array($raw, array_map(fn(string $item): string => $this->fold($item), $values), true)) {
                return $type;
            }
        }

        return in_array($raw, self::TYPES, true) ? $raw : null;
    }

    private function normalizeLevel(mixed $value): ?string
    {
        $raw = $this->fold((string) $value);
        if ($raw === '') {
            return 'unspecified';
        }

        $aliases = [
            'unspecified' => ['unspecified','non specificato'],
            'international' => ['international','internazionale'],
            'national' => ['national','nazionale'],
            'regional' => ['regional','regionale'],
            'provincial' => ['provincial','provinciale'],
            'local' => ['local','locale'],
            'branch' => ['branch','sezione','sede'],
            'other' => ['other','altro'],
        ];

        foreach ($aliases as $level => $values) {
            if (in_array($raw, array_map(fn(string $item): string => $this->fold($item), $values), true)) {
                return $level;
            }
        }

        return in_array($raw, self::LEVELS, true) ? $raw : null;
    }

    private function normalizeOperationalStatus(mixed $value): ?string
    {
        $raw = $this->fold((string) $value);
        if ($raw === '') {
            return 'active';
        }

        $aliases = [
            'active' => ['active','attiva','attivo'],
            'inactive' => ['inactive','inattiva','inattivo'],
            'represented' => ['represented','rappresentata','rappresentato'],
            'commissaried' => ['commissaried','commissariata','commissariato'],
            'merged' => ['merged','fusa','fuso'],
            'dissolved' => ['dissolved','sciolta','sciolto'],
        ];

        foreach ($aliases as $status => $values) {
            if (in_array($raw, array_map(fn(string $item): string => $this->fold($item), $values), true)) {
                return $status;
            }
        }

        return in_array($raw, self::STATUSES, true) ? $raw : null;
    }

    private function normalizeAffiliationType(mixed $value): ?string
    {
        $raw = $this->fold((string) $value);
        if ($raw === '') {
            return 'sports_affiliation';
        }

        $aliases = [
            'sports_affiliation' => ['sports affiliation','affiliazione sportiva','sports_affiliation'],
            'institutional_affiliation' => ['institutional affiliation','affiliazione istituzionale','institutional_affiliation'],
            'membership' => ['membership','adesione','appartenenza'],
            'recognition' => ['recognition','riconoscimento'],
            'other' => ['other','altro'],
        ];

        foreach ($aliases as $type => $values) {
            if (in_array($raw, array_map(fn(string $item): string => $this->fold($item), $values), true)) {
                return $type;
            }
        }

        return null;
    }

    private function normalizeAffiliationStatus(mixed $value): ?string
    {
        $raw = $this->fold((string) $value);
        if ($raw === '') {
            return 'active';
        }

        $aliases = [
            'active' => ['active','attiva','attivo'],
            'pending' => ['pending','in attesa'],
            'suspended' => ['suspended','sospesa','sospeso'],
            'expired' => ['expired','scaduta','scaduto'],
            'inactive' => ['inactive','inattiva','inattivo'],
        ];

        foreach ($aliases as $status => $values) {
            if (in_array($raw, array_map(fn(string $item): string => $this->fold($item), $values), true)) {
                return $status;
            }
        }

        return null;
    }

    private function normalizeCountry(mixed $value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [null, null];
        }

        $key = $this->fold($raw);
        $aliases = $this->countryAliases();

        return isset($aliases[$key])
            ? [$aliases[$key], null]
            : [null, 'invalid_country'];
    }

    private function countryAliases(): array
    {
        if ($this->countryAliases !== null) {
            return $this->countryAliases;
        }

        $aliases = [];
        foreach (['it-IT', 'en-GB'] as $language) {
            foreach (CountryMetadata::countries($language) as $country) {
                $alpha2 = strtoupper((string) $country['alpha2']);
                foreach ([$country['alpha2'], $country['alpha3'], $country['name']] as $value) {
                    $aliases[$this->fold((string) $value)] = $alpha2;
                }
            }
        }

        $aliases[$this->fold('Holland')] = 'NL';
        $aliases[$this->fold('Olanda')] = 'NL';
        $aliases[$this->fold('UK')] = 'GB';
        $aliases[$this->fold('USA')] = 'US';

        return $this->countryAliases = $aliases;
    }

    private function normalizeIdentifier(mixed $value): ?string
    {
        $value = mb_strtoupper(trim((string) $value), 'UTF-8');
        $value = preg_replace('/\s+/u', '', $value) ?? '';
        return $value !== '' ? $value : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $prefix = str_starts_with($value, '+') ? '+' : '';
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        return $digits !== '' ? $prefix . $digits : null;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d','d/m/Y','d-m-Y','Y/m/d'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date instanceof DateTimeImmutable && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function cleanString(mixed $value, int $maxLength): ?string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }

    private function fold(mixed $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        if ($value === '') {
            return '';
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = is_string($transliterated) && $transliterated !== '' ? $transliterated : $value;
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value, 'UTF-8');
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
