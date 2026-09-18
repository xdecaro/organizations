<?php

$domainPath = dirname(__DIR__) . '/component/admin/src/Service/OrganizationDelegationDomain.php';
if (!is_file($domainPath)) {
    fwrite(STDERR, "OrganizationDelegationDomain implementation is missing.\n");
    exit(1);
}

define('_JEXEC', 1);
require $domainPath;

use xdecaro\Component\Organizations\Administrator\Service\OrganizationDelegationDomain;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};

$valid = OrganizationDelegationDomain::validate([
    'title' => 'Rapporti istituzionali',
    'starts_on' => '2026-09-18',
    'ends_on' => '2027-09-18',
]);
$assert($valid === [], 'valid delegation must pass validation');

$invalid = OrganizationDelegationDomain::validate([
    'title' => '',
    'starts_on' => '2027-01-01',
    'ends_on' => '2026-01-01',
]);
$assert($invalid !== [], 'invalid delegation must fail validation');

$today = new DateTimeImmutable('2026-09-18');
$assert(OrganizationDelegationDomain::status(['state' => 1, 'starts_on' => '2026-09-18'], $today) === 'active', 'active delegation failed');
$assert(OrganizationDelegationDomain::status(['state' => 0, 'starts_on' => '2026-09-18'], $today) === 'inactive', 'inactive delegation failed');
$assert(OrganizationDelegationDomain::status(['state' => 1, 'starts_on' => '2027-01-01'], $today) === 'scheduled', 'scheduled delegation failed');
$assert(OrganizationDelegationDomain::status(['state' => 1, 'starts_on' => '2025-01-01', 'ends_on' => '2026-09-17'], $today) === 'ended', 'ended delegation failed');

echo "organization delegation domain OK\n";
