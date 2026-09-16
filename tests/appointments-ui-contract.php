<?php

$root = dirname(__DIR__);
$form = (string) file_get_contents($root . '/component/admin/forms/organization.xml');
$template = (string) file_get_contents($root . '/component/admin/tmpl/organization/edit.php');
$membersPath = $root . '/component/admin/tmpl/organization/edit_members.php';
$members = is_file($membersPath) ? (string) file_get_contents($membersPath) : '';
$view = (string) file_get_contents($root . '/component/admin/src/View/Organization/HtmlView.php');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');
$css = (string) file_get_contents($root . '/component/media/css/admin.css');
$languageIt = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.ini');
$languageEn = (string) file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.ini');
$languageItSys = (string) file_get_contents($root . '/component/admin/language/it-IT/com_xdecaroorganizations.sys.ini');
$languageEnSys = (string) file_get_contents($root . '/component/admin/language/en-GB/com_xdecaroorganizations.sys.ini');

if (!preg_match('/<fieldset name="identity".*?<\/fieldset>/s', $form, $identity)
    || str_contains($identity[0], 'name="uuid"')) {
    fwrite(STDERR, "UUID must no longer be rendered in Identity.\n");
    exit(1);
}

if (!preg_match('/<fieldset name="system".*?<\/fieldset>/s', $form, $system)
    || !str_contains($system[0], 'name="uuid"')
    || !str_contains($system[0], 'name="created"')
    || !str_contains($system[0], 'name="modified"')) {
    fwrite(STDERR, "System fieldset must contain UUID, created and modified.\n");
    exit(1);
}

$positions = [];
foreach (['identity', 'contacts', 'members', 'publishing', 'system'] as $tab) {
    $positions[$tab] = strpos($template, "'{$tab}'");
    if ($positions[$tab] === false) {
        fwrite(STDERR, "Missing organization tab: {$tab}\n");
        exit(1);
    }
}
if (!($positions['identity'] < $positions['contacts']
    && $positions['contacts'] < $positions['members']
    && $positions['members'] < $positions['publishing']
    && $positions['publishing'] < $positions['system'])) {
    fwrite(STDERR, "Organization tabs are not in the approved order.\n");
    exit(1);
}

foreach (['data-appointment-add', 'data-appointment-edit', 'data-appointment-end', 'appointment-edit-modal', 'appointment-end-modal'] as $needle) {
    if (!str_contains($members, $needle)) {
        fwrite(STDERR, "Members UI is missing {$needle}.\n");
        exit(1);
    }
}

if (!str_contains($members, 'COM_XDECAROORGANIZATIONS_MEMBERS_ACTIVE')
    || !str_contains($members, 'COM_XDECAROORGANIZATIONS_MEMBERS_HISTORY')
    || !str_contains($members, 'COM_XDECAROORGANIZATIONS_MEMBERS_SAVE_FIRST')
    || !str_contains($members, 'COM_XDECAROORGANIZATIONS_PEOPLE_UNAVAILABLE')) {
    fwrite(STDERR, "Members UI must cover active/history/new-record/People-unavailable states.\n");
    exit(1);
}

foreach (['getPeopleIntegrationService', 'OrganizationAppointments', 'peopleAvailable', 'appointments'] as $needle) {
    if (!str_contains($view, $needle)) {
        fwrite(STDERR, "Organization view is missing members integration: {$needle}.\n");
        exit(1);
    }
}

foreach (['Log::add(', "enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error')", '$items === false'] as $needle) {
    if (!str_contains($view, $needle)) {
        fwrite(STDERR, "Appointment loading failures must be logged and surfaced: {$needle}.\n");
        exit(1);
    }
}

foreach (["task=appointment.searchPeople", "task=appointment.save", "task=appointment.end", 'data-duration-years', 'window.location.reload()', 'setUTCFullYear'] as $needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "Organization edit JS is missing members behavior: {$needle}.\n");
        exit(1);
    }
}

foreach ([
    '#appointment-edit-modal .modal-dialog',
    '#appointment-end-modal .modal-dialog',
    'max-width: 720px;',
    'align-items: center;',
    '#appointment-edit-modal .modal-body',
    'overflow-x: hidden;',
] as $needle) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "Appointment modal CSS is missing {$needle}.\n");
        exit(1);
    }
}

foreach ([$languageIt, $languageEn, $languageItSys, $languageEnSys] as $language) {
    if (!str_contains($language, 'COM_XDECAROORGANIZATIONS="Organizations"')
        || str_contains($language, 'Organizations by xdecaro')) {
        fwrite(STDERR, "Administrator menu title must be Organizations without 'by xdecaro'.\n");
        exit(1);
    }
}

echo "appointments UI contract OK\n";
