<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use DateTimeImmutable;
use InvalidArgumentException;

final class AppointmentDomain
{
    private const ROLES = [
        'president',
        'vice_president',
        'secretary',
        'treasurer',
        'councillor',
        'auditor',
        'director',
        'coordinator',
        'custom',
    ];

    private const END_REASONS = [
        'term_end',
        'resignation',
        'revocation',
        'forfeiture',
        'other',
    ];

    public static function roles(): array
    {
        return self::ROLES;
    }

    public static function endReasons(): array
    {
        return self::END_REASONS;
    }

    public static function plannedEnd(string $startsOn, ?int $years, ?string $provided): ?string
    {
        $provided = trim((string) ($provided ?? ''));
        if ($provided !== '') {
            if (!self::validDate($provided)) {
                throw new InvalidArgumentException('Invalid planned end date.');
            }

            return $provided;
        }

        if ($years === null) {
            return null;
        }

        if ($years < 1 || $years > 5) {
            throw new InvalidArgumentException('Duration years must be between 1 and 5.');
        }

        if (!self::validDate($startsOn)) {
            throw new InvalidArgumentException('Invalid appointment start date.');
        }

        return (new DateTimeImmutable($startsOn))->modify('+' . $years . ' years')->format('Y-m-d');
    }

    public static function validate(array $data): array
    {
        $errors = [];
        $personUuid = strtolower(trim((string) ($data['person_uuid'] ?? '')));
        $roleCode = trim((string) ($data['role_code'] ?? ''));
        $roleCustom = trim((string) ($data['role_custom'] ?? ''));
        $startsOn = trim((string) ($data['starts_on'] ?? ''));
        $plannedEndsOn = trim((string) ($data['planned_ends_on'] ?? ''));
        $endedOn = trim((string) ($data['ended_on'] ?? ''));
        $endReason = trim((string) ($data['end_reason'] ?? ''));

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $personUuid)) {
            $errors['person_uuid'] = 'Invalid person UUID.';
        }

        if (!in_array($roleCode, self::ROLES, true)) {
            $errors['role_code'] = 'Invalid appointment role.';
        }

        if ($roleCode === 'custom' && $roleCustom === '') {
            $errors['role_custom'] = 'Custom role is required.';
        }

        if (!self::validDate($startsOn)) {
            $errors['starts_on'] = 'Invalid appointment start date.';
        }

        if ($plannedEndsOn !== '' && !self::validDate($plannedEndsOn)) {
            $errors['planned_ends_on'] = 'Invalid planned end date.';
        }

        if ($endedOn !== '' && !self::validDate($endedOn)) {
            $errors['ended_on'] = 'Invalid actual end date.';
        }

        if ($endReason !== '' && !in_array($endReason, self::END_REASONS, true)) {
            $errors['end_reason'] = 'Invalid end reason.';
        }

        if ($endedOn !== '' && $endReason === '') {
            $errors['end_reason'] = 'End reason is required when an actual end date is set.';
        }

        if ($endReason !== '' && $endedOn === '') {
            $errors['ended_on'] = 'Actual end date is required when an end reason is set.';
        }

        if (self::validDate($startsOn) && self::validDate($plannedEndsOn) && $plannedEndsOn < $startsOn) {
            $errors['planned_ends_on'] = 'Planned end date cannot precede the start date.';
        }

        if (self::validDate($startsOn) && self::validDate($endedOn) && $endedOn < $startsOn) {
            $errors['ended_on'] = 'Actual end date cannot precede the start date.';
        }

        return $errors;
    }

    public static function status(array $appointment, ?DateTimeImmutable $today = null): string
    {
        $endReason = trim((string) ($appointment['end_reason'] ?? ''));

        if ($endReason !== '') {
            return match ($endReason) {
                'resignation' => 'resigned',
                'revocation' => 'revoked',
                'forfeiture' => 'forfeited',
                'term_end', 'other' => 'ended',
                default => 'ended',
            };
        }

        $plannedEndsOn = trim((string) ($appointment['planned_ends_on'] ?? ''));
        $today ??= new DateTimeImmutable('today');

        if (self::validDate($plannedEndsOn) && $plannedEndsOn < $today->format('Y-m-d')) {
            return 'expired';
        }

        return 'active';
    }

    public static function roleLabelKey(string $roleCode): string
    {
        return match ($roleCode) {
            'president' => 'COM_XDECAROORGANIZATIONS_ROLE_PRESIDENT',
            'vice_president' => 'COM_XDECAROORGANIZATIONS_ROLE_VICE_PRESIDENT',
            'secretary' => 'COM_XDECAROORGANIZATIONS_ROLE_SECRETARY',
            'treasurer' => 'COM_XDECAROORGANIZATIONS_ROLE_TREASURER',
            'councillor' => 'COM_XDECAROORGANIZATIONS_ROLE_COUNCILLOR',
            'auditor' => 'COM_XDECAROORGANIZATIONS_ROLE_AUDITOR',
            'director' => 'COM_XDECAROORGANIZATIONS_ROLE_DIRECTOR',
            'coordinator' => 'COM_XDECAROORGANIZATIONS_ROLE_COORDINATOR',
            'custom' => 'COM_XDECAROORGANIZATIONS_ROLE_CUSTOM',
            default => 'COM_XDECAROORGANIZATIONS_ROLE_CUSTOM',
        };
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
