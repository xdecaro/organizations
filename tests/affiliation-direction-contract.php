<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$sql = (string) file_get_contents($root . '/component/admin/sql/updates/mysql/1.2.11.sql');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAffiliationModel.php');
$listModel = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationsModel.php');
$affiliationsModel = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationAffiliationsModel.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_affiliations.php');
$listTemplate = (string) file_get_contents($root . '/component/admin/tmpl/organizations/default.php');
$it = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');

$checks = [
    [$sql, "source_org.type = 'federation'", 'Migration must detect federation as the incorrect source.'],
    [$sql, "target_org.type = 'club'", 'Migration must detect club as the incorrect target.'],
    [$sql, 'wrong.organization_id = target_org.id', 'Migration must move the club to organization_id.'],
    [$sql, 'wrong.target_organization_id = source_org.id', 'Migration must move the federation to target_organization_id.'],
    [$model, "\$sourceType === 'federation' && \$targetType === 'club'", 'Future reverse club/federation sports affiliations must be rejected.'],
    [$model, 'COM_XDECAROORGANIZATIONS_AFFILIATION_DIRECTION_CLUB_TO_FEDERATION', 'Direction validation must provide a translated explanation.'],
    [$affiliationsModel, 'setTargetOrganizationId', 'Affiliation list model must support incoming affiliates.'],
    [$affiliationsModel, 'source_name', 'Incoming affiliate rows must expose the source organization.'],
    [$template, 'COM_XDECAROORGANIZATIONS_AFFILIATES_TITLE', 'Organization editor must show a separate affiliates section.'],
    [$template, 'source_name', 'Affiliates section must render the incoming organization.'],
    [$listModel, ') AS affiliation_count', 'Organizations list must count outgoing affiliations separately.'],
    [$listModel, ') AS affiliate_count', 'Organizations list must count incoming affiliates separately.'],
    [$listTemplate, 'data-org-column="affiliates"', 'Organizations list must expose the optional Affiliates column.'],
    [$it, 'COM_XDECAROORGANIZATIONS_COLUMN_AFFILIATES="Affiliati"', 'Italian Affiliati label is required.'],
];

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Affiliation direction contract OK\n";
