<?php

$domainPath = dirname(__DIR__) . '/component/admin/src/Service/OrganizationBodyDomain.php';
if (!is_file($domainPath)) {
    fwrite(STDERR, "OrganizationBodyDomain implementation is missing.\n");
    exit(1);
}

define('_JEXEC', 1);
require $domainPath;

use xdecaro\Component\Organizations\Administrator\Service\OrganizationBodyDomain;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};

$types = OrganizationBodyDomain::types();
foreach (['congress', 'assembly', 'board', 'control_body', 'commission', 'committee', 'department', 'sector', 'other'] as $type) {
    $assert(in_array($type, $types, true), "missing body type {$type}");
}

$valid = OrganizationBodyDomain::validate([
    'name' => 'Consiglio',
    'body_type' => 'board',
    'starts_on' => '2026-01-01',
    'ends_on' => '2030-12-31',
]);
$assert($valid === [], 'valid organization body must pass validation');

$invalid = OrganizationBodyDomain::validate([
    'name' => '',
    'body_type' => 'unknown',
    'starts_on' => '2030-01-01',
    'ends_on' => '2029-01-01',
]);
$assert($invalid !== [], 'invalid organization body must fail validation');

$today = new DateTimeImmutable('2026-09-18');
$assert(OrganizationBodyDomain::status(['state' => 1], $today) === 'active', 'active status failed');
$assert(OrganizationBodyDomain::status(['state' => 0], $today) === 'inactive', 'inactive status failed');
$assert(OrganizationBodyDomain::status(['state' => 1, 'starts_on' => '2027-01-01'], $today) === 'scheduled', 'scheduled status failed');
$assert(OrganizationBodyDomain::status(['state' => 1, 'ends_on' => '2026-09-17'], $today) === 'ended', 'ended status failed');

echo "organization body domain OK\n";
