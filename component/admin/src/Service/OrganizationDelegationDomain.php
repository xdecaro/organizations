<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeImmutable;

final class OrganizationDelegationDomain
{
    public static function validate(array $data): array
    {
        $errors = [];
        $title = trim((string) ($data['title'] ?? ''));
        $startsOn = trim((string) ($data['starts_on'] ?? ''));
        $endsOn = trim((string) ($data['ends_on'] ?? ''));

        if ($title === '') {
            $errors['title'] = 'Delegation title is required.';
        }

        if (!self::validDate($startsOn)) {
            $errors['starts_on'] = 'Invalid delegation start date.';
        }

        if ($endsOn !== '' && !self::validDate($endsOn)) {
            $errors['ends_on'] = 'Invalid delegation end date.';
        }

        if (self::validDate($startsOn) && self::validDate($endsOn) && $endsOn < $startsOn) {
            $errors['ends_on'] = 'Delegation end date cannot precede the start date.';
        }

        return $errors;
    }

    public static function status(array|object $delegation, ?DateTimeImmutable $today = null): string
    {
        $row = is_object($delegation) ? get_object_vars($delegation) : $delegation;

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
