<?php

$root = dirname(__DIR__);
$modelPath = $root . '/component/admin/src/Model/OrganizationAppointmentModel.php';
$listPath = $root . '/component/admin/src/Model/OrganizationAppointmentsModel.php';
$controllerPath = $root . '/component/admin/src/Controller/AppointmentController.php';
$providerPath = $root . '/component/admin/services/provider.php';

$model = is_file($modelPath) ? (string) file_get_contents($modelPath) : '';
$list = is_file($listPath) ? (string) file_get_contents($listPath) : '';
$controller = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
$provider = is_file($providerPath) ? (string) file_get_contents($providerPath) : '';

$checks = [
    [$model, 'function saveAppointment', 'Appointment model must expose saveAppointment().'],
    [$model, 'function endAppointment', 'Appointment model must expose endAppointment().'],
    [$model, 'function deleteAppointment', 'Appointment model must expose deleteAppointment() for deleting one appointment by id.'],
    [$model, 'getPeopleIntegrationService', 'Appointment model must resolve People through the Organizations service.'],
    [$model, 'getPerson($personUuid)', 'New or changed people must be resolved server-side.'],
    [$model, "'person_name_snapshot'", 'Appointment model must persist a person-name snapshot.'],
    [$list, 'function setOrganizationId', 'Appointments list model must be scoped to one organization.'],
    [$list, '#__xdecaroorganizations_appointments', 'Appointments list model must read the local appointments table.'],
    [$list, 'AppointmentDomain::status', 'Appointments list model must derive visual status.'],
    [$controller, "Session::checkToken('post')", 'Appointment JSON actions must check CSRF tokens.'],
    [$controller, "authorise('core.create'", 'Creating an appointment must require core.create.'],
    [$controller, "authorise('core.edit'", 'Editing/ending an appointment must require core.edit.'],
    [$controller, "authorise('core.delete'", 'Deleting an appointment must require core.delete.'],
    [$controller, 'function delete', 'Appointment controller must expose a delete action.'],
    [$controller, 'new JsonResponse', 'Appointment controller must return Joomla JSON responses.'],
    [$provider, 'setMVCFactory($container->get(MVCFactoryInterface::class))', 'OrganizationsComponent must receive the Joomla MVC factory explicitly.'],
];

foreach ($checks as [$source, $needle, $message]) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

if (!preg_match('/function\s+endAppointment\s*\([^)]*\).*?\n\s*}\n/s', $model, $match)) {
    fwrite(STDERR, "Unable to inspect endAppointment().\n");
    exit(1);
}

if (str_contains($match[0], "planned_ends_on") || str_contains($match[0], "plannedEndsOn")) {
    fwrite(STDERR, "Ending an appointment must not overwrite its planned end date.\n");
    exit(1);
}

if (preg_match('/new\s+OrganizationsComponent\s*\([^;]*MVCFactoryInterface::class/s', $provider)) {
    fwrite(STDERR, "MVCFactory must be injected with setMVCFactory(), not as an extra constructor argument.\n");
    exit(1);
}

$schema = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
if (preg_match('/UNIQUE[^\n]*(organization_id|person_uuid|role_code)/i', $schema)) {
    fwrite(STDERR, "Concurrent roles must not be blocked by a composite UNIQUE constraint.\n");
    exit(1);
}

if (str_contains($model . $list . $controller, '#__xdecaropeople_')) {
    fwrite(STDERR, "Appointments backend must not query People tables directly.\n");
    exit(1);
}

echo "appointments backend contract OK\n";
