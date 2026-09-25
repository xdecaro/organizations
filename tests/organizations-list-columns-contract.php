<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$template = (string) file_get_contents($root . '/component/admin/tmpl/organizations/default.php');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationsModel.php');
$view = (string) file_get_contents($root . '/component/admin/src/View/Organizations/HtmlView.php');
$asset = (string) file_get_contents($root . '/component/media/joomla.asset.json');
$script = (string) file_get_contents($root . '/component/media/js/organizations-list.js');
$it = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$en = (string) file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini');

$checks = [
    [$template, "HTMLHelper::_('jgrid.published'", 'Organizations list must use Joomla icon-based published state control.'],
    [$template, "'organizations.'", 'Published state control must target the organizations list controller.'],
    [$template, 'data-org-column-picker', 'Organizations list must expose a column chooser.'],
    [$template, 'data-org-column="affiliations"', 'Organizations list must expose the affiliations column.'],
    [$template, 'affiliation_count', 'Organizations list must render the affiliation count.'],
    [$model, '#__xdecaroorganizations_affiliations', 'Organizations list model must count affiliations.'],
    [$model, "af.status", 'Affiliation count must limit itself to current active relations.'],
    [$model, 'af.organization_id', 'Affiliation count must include outgoing relations.'],
    [$model, 'af.target_organization_id', 'Affiliation count must include incoming relations.'],
    [$view, "useScript('com_xdecaroorganizations.organizations-list')", 'Organizations list must load the column preference script.'],
    [$view, "useScript('bootstrap.dropdown')", 'Organizations list must load the dropdown behavior.'],
    [$asset, 'com_xdecaroorganizations.organizations-list', 'Organizations list script must be registered as a Joomla web asset.'],
    [$script, 'window.localStorage', 'Column choices must persist in the browser.'],
    [$script, 'data-org-column', 'Column script must control table cells by column key.'],
    [$it, 'COM_XDECAROORGANIZATIONS_COLUMN_AFFILIATIONS="Affiliazioni"', 'Italian affiliations column label is required.'],
    [$it, 'COM_XDECAROORGANIZATIONS_COLUMNS="Colonne"', 'Italian columns chooser label is required.'],
    [$en, 'COM_XDECAROORGANIZATIONS_COLUMNS="Columns"', 'English columns chooser label is required.'],
];

if (str_contains($it, '\\nCOM_XDECAROORGANIZATIONS_') || str_contains($en, '\\nCOM_XDECAROORGANIZATIONS_')) {
    fwrite(STDERR, "Language files must contain real line breaks, not literal \\n sequences.\n");
    exit(1);
}

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Organizations list columns contract OK\n";
