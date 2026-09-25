<?php

define('_JEXEC', 1);

$root = dirname(__DIR__);
require_once $root . '/component/admin/src/Service/OrganizationDelegationDomain.php';

use xdecaro\Component\Organizations\Administrator\Service\OrganizationDelegationDomain;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};

$assert(
    OrganizationDelegationDomain::effectiveEnd([
        'ends_on' => null,
        'appointment_planned_ends_on' => '2027-09-14',
        'appointment_ended_on' => null,
    ]) === '2027-09-14',
    'delegation without own end must be bounded by planned appointment end'
);

$assert(
    OrganizationDelegationDomain::effectiveEnd([
        'ends_on' => '2027-01-10',
        'appointment_planned_ends_on' => '2027-09-14',
        'appointment_ended_on' => null,
    ]) === '2027-01-10',
    'earlier delegation end must win over planned appointment end'
);

$assert(
    OrganizationDelegationDomain::effectiveEnd([
        'ends_on' => null,
        'appointment_planned_ends_on' => '2027-09-14',
        'appointment_ended_on' => '2026-12-01',
    ]) === '2026-12-01',
    'actual appointment end must override planned end'
);

$today = new DateTimeImmutable('2027-09-15');
$assert(
    OrganizationDelegationDomain::status([
        'state' => 1,
        'starts_on' => '2026-09-14',
        'ends_on' => null,
        'appointment_planned_ends_on' => '2027-09-14',
        'appointment_ended_on' => null,
    ], $today) === 'ended',
    'delegation must end when the linked mandate planned end is reached'
);

echo "delegation mandate boundary domain OK\n";
