<?php

$root = dirname(__DIR__);
$path = $root . '/component/admin/src/Service/AppointmentMembershipPolicyService.php';

if (!is_file($path)) {
    fwrite(STDERR, "AppointmentMembershipPolicyService implementation is missing.\n");
    exit(1);
}

define('_JEXEC', 1);
require $path;

use xdecaro\Component\Organizations\Administrator\Service\AppointmentMembershipPolicyService;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};

foreach (['inherit', 'none', 'active_member', 'active_member_fee_current'] as $value) {
    $assert(AppointmentMembershipPolicyService::normalizeRequirement($value) === $value, "invalid normalization for {$value}");
}

$assert(
    AppointmentMembershipPolicyService::normalizeRequirement('bad') === 'inherit',
    'unknown requirement must normalize to inherit'
);

echo "appointment membership policy domain OK\n";
