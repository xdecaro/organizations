<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

final class OrganizationAffiliationDomain
{
    public const TYPES = [
        'sports_affiliation',
        'institutional_affiliation',
        'membership',
        'recognition',
        'other',
    ];

    public const STATUSES = [
        'active',
        'pending',
        'suspended',
        'expired',
        'inactive',
    ];

    public static function validate(array $data): array
    {
        $errors = [];
        $organizationId = (int) ($data['organization_id'] ?? 0);
        $targetId = (int) ($data['target_organization_id'] ?? 0);
        $type = (string) ($data['relation_type'] ?? '');
        $status = (string) ($data['status'] ?? '');
        $startsOn = trim((string) ($data['starts_on'] ?? ''));
        $endsOn = trim((string) ($data['ends_on'] ?? ''));

        if ($organizationId < 1) {
            $errors['organization_id'] = 'Organization is required.';
        }
        if ($targetId < 1) {
            $errors['target_organization_id'] = 'Affiliated organization is required.';
        }
        if ($organizationId > 0 && $organizationId === $targetId) {
            $errors['target_organization_id'] = 'An organization cannot be affiliated to itself.';
        }
        if (!in_array($type, self::TYPES, true)) {
            $errors['relation_type'] = 'Invalid affiliation type.';
        }
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Invalid affiliation status.';
        }
        if ($startsOn !== '' && !self::validDate($startsOn)) {
            $errors['starts_on'] = 'Invalid start date.';
        }
        if ($endsOn !== '' && !self::validDate($endsOn)) {
            $errors['ends_on'] = 'Invalid end date.';
        }
        if ($startsOn !== '' && $endsOn !== '' && $endsOn < $startsOn) {
            $errors['ends_on'] = 'End date cannot be earlier than start date.';
        }

        return $errors;
    }

    public static function typeLabelKey(string $type): string
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'other';

        return 'COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_' . strtoupper($type);
    }

    public static function statusLabelKey(string $status): string
    {
        $status = in_array($status, self::STATUSES, true) ? $status : 'inactive';

        return 'COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_' . strtoupper($status);
    }

    private static function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
