<?php
$root = dirname(__DIR__);
$view = file_get_contents($root . '/component/admin/src/View/Organizations/HtmlView.php');

if (str_contains($view, 'ToolbarHelper::deleteList') || str_contains($view, "'organizations.delete'")) {
    fwrite(STDERR, "Organizations toolbar must not expose permanent delete.\n");
    exit(1);
}

if (!str_contains($view, "ToolbarHelper::trash('organizations.trash')")) {
    fwrite(STDERR, "Organizations toolbar must keep the recoverable trash action.\n");
    exit(1);
}

echo "Organizations no-delete toolbar contract OK\n";
