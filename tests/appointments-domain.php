<?php

$domainPath = dirname(__DIR__) . '/component/admin/src/Service/AppointmentDomain.php';
if (!is_file($domainPath)) {
    fwrite(STDERR, "AppointmentDomain implementation is missing.\n");
    exit(1);
}

define('_JEXEC', 1);
require $domainPath;

use xdecaro\Component\Organizations\Administrator\Service\AppointmentDomain;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};

$assert(AppointmentDomain::plannedEnd('2026-09-16', 1, null) === '2027-09-16', '1 year duration failed');
$assert(AppointmentDomain::plannedEnd('2026-09-16', 5, null) === '2031-09-16', '5 year duration failed');
$assert(AppointmentDomain::plannedEnd('2026-09-16', 3, '2029-10-01') === '2029-10-01', 'manual override must win');
$assert(AppointmentDomain::plannedEnd('2026-09-16', null, '2028-03-16') === '2028-03-16', 'custom date must be preserved');

$today = new DateTimeImmutable('2026-09-16');
$assert(AppointmentDomain::status(['planned_ends_on' => '2026-09-16', 'ended_on' => null, 'end_reason' => null], $today) === 'active', 'appointment ending today is active');
$assert(AppointmentDomain::status(['starts_on' => '2026-09-17', 'planned_ends_on' => '2027-09-17', 'ended_on' => null, 'end_reason' => null], $today) === 'scheduled', 'future appointment must be scheduled');
$assert(AppointmentDomain::status(['planned_ends_on' => '2026-09-15', 'ended_on' => null, 'end_reason' => null], $today) === 'expired', 'past appointment must expire');
$assert(AppointmentDomain::status(['end_reason' => 'term_end', 'ended_on' => '2026-09-10'], $today) === 'ended', 'term_end mapping failed');
$assert(AppointmentDomain::status(['end_reason' => 'resignation', 'ended_on' => '2026-09-10'], $today) === 'resigned', 'resignation mapping failed');
$assert(AppointmentDomain::status(['end_reason' => 'revocation', 'ended_on' => '2026-09-10'], $today) === 'revoked', 'revocation mapping failed');
$assert(AppointmentDomain::status(['end_reason' => 'forfeiture', 'ended_on' => '2026-09-10'], $today) === 'forfeited', 'forfeiture mapping failed');
$assert(AppointmentDomain::status(['end_reason' => 'other', 'ended_on' => '2026-09-10'], $today) === 'ended', 'other mapping failed');

$errors = AppointmentDomain::validate([
    'person_uuid' => 'bad',
    'role_code' => 'custom',
    'role_custom' => '',
    'starts_on' => '2026-09-16',
    'planned_ends_on' => '2026-09-15',
]);
$assert($errors !== [], 'invalid UUID/custom role/date ordering must fail');

$valid = AppointmentDomain::validate([
    'person_uuid' => '123e4567-e89b-42d3-a456-426614174000',
    'role_code' => 'councillor',
    'role_custom' => null,
    'starts_on' => '2026-09-16',
    'planned_ends_on' => '2031-09-16',
    'ended_on' => null,
    'end_reason' => null,
]);
$assert($valid === [], 'valid appointment must pass validation');

$endedBeforeStart = AppointmentDomain::validate([
    'person_uuid' => '123e4567-e89b-42d3-a456-426614174000',
    'role_code' => 'president',
    'starts_on' => '2026-09-16',
    'ended_on' => '2026-09-15',
    'end_reason' => 'resignation',
]);
$assert($endedBeforeStart !== [], 'termination before start must fail');

$roles = AppointmentDomain::roles();
$assert(in_array('president', $roles, true) && in_array('custom', $roles, true), 'role catalog incomplete');
foreach (['representative', 'commissioner', 'vice_commissioner', 'delegate', 'control_member', 'administrative_secretary'] as $role) {
    $assert(in_array($role, $roles, true), "missing generic organization role {$role}");
}
$assert(count($roles) === 15, 'role catalog must contain the approved generic organization roles');

echo "appointments domain OK\n";
