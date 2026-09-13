<?php
$controller = file_get_contents(__DIR__ . '/../component/admin/src/Controller/OrganizationController.php');
$compact = preg_replace('/\s+/', '', $controller);

foreach ([
    "protected\$option='com_xdecaroorganizations';",
    "protected\$view_item='organization';",
    "protected\$view_list='organizations';",
] as $required) {
    if (!str_contains($compact, $required)) {
        fwrite(STDERR, "Missing OrganizationController contract: {$required}\n");
        exit(1);
    }
}

echo "OrganizationController route contract OK\n";
