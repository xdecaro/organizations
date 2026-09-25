<?php
$path = __DIR__ . '/../component/admin/tmpl/organizations/default.php';
$template = file_get_contents($path);
if ($template === false) {
    fwrite(STDERR, "Unable to read organizations list template.\n");
    exit(1);
}

if (strpos($template, 'value="-2"') === false || strpos($template, "Text::_('JTRASHED')") === false) {
    fwrite(STDERR, "Organizations state filter must expose and label the trashed state (-2).\n");
    exit(1);
}

if (strpos($template, "HTMLHelper::_('jgrid.published'") === false) {
    fwrite(STDERR, "Organizations row state must use Joomla's icon-based published control.\n");
    exit(1);
}

echo "Organizations trashed filter contract OK\n";
