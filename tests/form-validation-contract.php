<?php
$template = file_get_contents(__DIR__ . '/../component/admin/tmpl/organization/edit.php');

if (!str_contains($template, "HTMLHelper::_('behavior.formvalidator')")) {
    fwrite(STDERR, "Organization edit form must load Joomla formvalidator behavior\n");
    exit(1);
}

echo "Organization form validation contract OK\n";
