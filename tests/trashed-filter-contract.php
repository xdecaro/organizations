<?php
$path = __DIR__ . '/../component/admin/tmpl/organizations/default.php';
$template = file_get_contents($path);
if ($template === false) {
    fwrite(STDERR, "Unable to read organizations list template.\n");
    exit(1);
}

if (strpos($template, 'value="-2"') === false) {
    fwrite(STDERR, "Organizations state filter must expose the trashed state (-2).\n");
    exit(1);
}

if (substr_count($template, "Text::_('JTRASHED')") < 2) {
    fwrite(STDERR, "Organizations list must label trashed state in both the filter and row status.\n");
    exit(1);
}
