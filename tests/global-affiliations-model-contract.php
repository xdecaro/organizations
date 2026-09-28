<?php

declare(strict_types=1);

$path = __DIR__ . '/../component/admin/src/Model/AffiliationsModel.php';
if (!is_file($path)) {
    fwrite(STDERR, "Missing AffiliationsModel.php\n");
    exit(1);
}

$source = (string) file_get_contents($path);
$required = [
    "final class AffiliationsModel extends ListModel",
    "#__xdecaroorganizations_affiliations",
    "source_name",
    "target_name",
    "filter.search",
    "filter.source",
    "filter.target",
    "filter.relation_type",
    "filter.status",
    "filter.temporal",
    "filter.perspective",
    "OrganizationAffiliationDomain::typeLabelKey",
    "OrganizationAffiliationDomain::statusLabelKey",
    "->bind(':sourceId'",
    "->bind(':targetId'",
];

foreach ($required as $needle) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, "AffiliationsModel missing: $needle\n");
        exit(1);
    }
}

if (str_contains($source, 'setOrganizationId(')) {
    fwrite(STDERR, "Global AffiliationsModel must not require a single organization id\n");
    exit(1);
}

fwrite(STDOUT, "global affiliations model contract: OK\n");
