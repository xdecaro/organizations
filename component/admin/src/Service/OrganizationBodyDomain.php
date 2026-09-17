<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeImmutable;

final class OrganizationBodyDomain
{
    private const TYPES = [
        'congress',
        'assembly',
        'board',
        'presidency',
        'secretariat',
        'control_body',
        'audit_body',
        'disciplinary_body',
        'youth_body',
        'commission',
        'committee',
        'department',
        'sector',
        'office',
        'other',
    ];

    public static function types(): array
    {
        return self::TYPES;
    }

    public static function validate(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        $type = trim((string) ($data['body_type'] ?? ''));
        $startsOn = trim((string) ($data['starts_on'] ?? ''));
        $endsOn = trim((string) ($data['ends_on'] ?? ''));

        if ($name === '') {
            $errors['name'] = 'Body name is required.';
        }

        if (!in_array($type, self::TYPES, true)) {
            $errors['body_type'] = 'Invalid organization body type.';
        }

        if ($startsOn !== '' && !self::validDate($startsOn)) {
            $errors['starts_on'] = 'Invalid body start date.';
        }

        if ($endsOn !== '' && !self::validDate($endsOn)) {
            $errors['ends_on'] = 'Invalid body end date.';
        }

        if (self::validDate($startsOn) && self::validDate($endsOn) && $endsOn < $startsOn) {
            $errors['ends_on'] = 'Body end date cannot precede the start date.';
        }

        return $errors;
    }

    public static function status(array|object $body, ?DateTimeImmutable $today = null): string
    {
        $row = is_object($body) ? get_object_vars($body) : $body;
        if ((int) ($row['state'] ?? 1) !== 1) {
            return 'inactive';
        }

        $today ??= new DateTimeImmutable('today');
        $todayValue = $today->format('Y-m-d');
        $startsOn = trim((string) ($row['starts_on'] ?? ''));
        $endsOn = trim((string) ($row['ends_on'] ?? ''));

        if (self::validDate($startsOn) && $startsOn > $todayValue) {
            return 'scheduled';
        }

        if (self::validDate($endsOn) && $endsOn < $todayValue) {
            return 'ended';
        }

        return 'active';
    }

    public static function typeLabelKey(string $type): string
    {
        return 'COM_XDECAROORGANIZATIONS_BODY_TYPE_' . strtoupper($type);
    }

    private static function validDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value;
    }
}
