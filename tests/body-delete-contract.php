<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$controller = (string) file_get_contents($root . '/component/admin/src/Controller/BodyController.php');
$model = (string) file_get_contents($root . '/component/admin/src/Model/OrganizationBodyModel.php');
$view = (string) file_get_contents($root . '/component/admin/src/View/Organization/HtmlView.php');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit_bodies.php');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');

foreach ([
    [$controller, 'public function delete(): void', 'Body controller must expose a delete action.'],
    [$controller, "Session::checkToken('post')", 'Body deletion must be CSRF protected.'],
    [$controller, "authorise('core.delete'", 'Body deletion must enforce core.delete ACL server-side.'],
    [$model, 'public function deleteBody(int $id): void', 'Body model must own deletion rules.'],
    [$model, "dependencyCount('#__xdecaroorganizations_bodies', 'parent_id'", 'Body deletion must detect child bodies.'],
    [$model, "dependencyCount('#__xdecaroorganizations_appointments', 'body_id'", 'Body deletion must detect linked appointments.'],
    [$model, 'transactionStart()', 'Body deletion must be transactional.'],
    [$model, 'forUpdate()', 'Body deletion must lock the body before dependency checks.'],
    [$view, 'public bool $canDeleteBodies = false;', 'Organization view must expose body delete permission.'],
    [$template, 'data-body-delete', 'Bodies UI must expose delete only through the guarded action.'],
    [$js, "task=body.delete&format=json", 'Admin JS must call the body delete endpoint.'],
] as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Organizations body delete contract OK\n";
