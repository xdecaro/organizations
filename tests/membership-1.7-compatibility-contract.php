<?php

$root = dirname(__DIR__);
$service = (string) file_get_contents($root . '/component/admin/src/Service/MembershipIntegrationService.php');

foreach ([
    "getMembershipEligibilityService",
    "getSnapshot",
    "isFeeCurrent",
    "getMembershipPersonHistoryService",
    "getHistoryByPersonUuid",
    "'not_member'",
    "'inactive_member'",
    "'fee_not_current'",
    "'eligible'",
] as $needle) {
    if (!str_contains($service, $needle)) {
        fwrite(STDERR, "Membership adapter contract missing {$needle}.\n");
        exit(1);
    }
}

if (strpos($service, "getMembershipEligibilityService") > strpos($service, "getMembershipPersonHistoryService")) {
    fwrite(STDERR, "Current Membership eligibility contract must be preferred over the legacy history contract.\n");
    exit(1);
}

echo "Organizations Membership 1.7 compatibility contract OK\n";
