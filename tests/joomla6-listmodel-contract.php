<?php
$model = file_get_contents(__DIR__ . '/../component/admin/src/Model/OrganizationsModel.php');

if (str_contains($model, '->getApplication()')) {
    fwrite(STDERR, "OrganizationsModel must not call getApplication() on ListModel\n");
    exit(1);
}

if (!str_contains($model, '$this->getUserStateFromRequest(')) {
    fwrite(STDERR, "OrganizationsModel must read request state through ListModel::getUserStateFromRequest()\n");
    exit(1);
}

echo "Joomla 6 ListModel contract OK\n";
