<?php

$root = dirname(__DIR__);
$service = (string) file_get_contents($root . '/component/admin/src/Service/PeopleIntegrationService.php');
$controller = (string) file_get_contents($root . '/component/admin/src/Controller/AppointmentController.php');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');

if (!str_contains($service, "method_exists(\$provider, 'searchPeopleForIdentity')")) {
    fwrite(STDERR, "Organizations must prefer the limited People identity-disambiguation provider when available.\n");
    exit(1);
}

if (!str_contains($service, "\$filters = ['search' => trim(\$search)]")
    || !preg_match('/searchPeopleForIdentity\(\s*\$filters,\s*\$limit\s*\)/s', $service)) {
    fwrite(STDERR, "Organizations must request birth disambiguation through searchPeopleForIdentity().\n");
    exit(1);
}

if (!preg_match('/searchPeople\(\s*\$filters,\s*\$limit,\s*false\s*\)/s', $service)) {
    fwrite(STDERR, "Organizations must retain a public name-only People fallback.\n");
    exit(1);
}

if (preg_match('/searchPeople\([^;]*,\s*true\s*\)/s', $service)) {
    fwrite(STDERR, "Organizations must not request the full sensitive People profile just to disambiguate homonyms.\n");
    exit(1);
}

foreach (["'birth_date' =>", "'birth_place' =>"] as $needle) {
    if (!str_contains($controller, $needle)) {
        fwrite(STDERR, "Appointment People search response must expose optional {$needle} metadata.\n");
        exit(1);
    }
}

foreach (['person.birth_date', 'person.birth_place', 'formatPersonBirthDetails'] as $needle) {
    if (!str_contains($js, $needle)) {
        fwrite(STDERR, "People autocomplete must render birth details for disambiguation: {$needle}.\n");
        exit(1);
    }
}

if (str_contains($js, 'option.textContent = person.name;')) {
    fwrite(STDERR, "People autocomplete must render name and birth metadata as separate lines, not name-only text.\n");
    exit(1);
}

echo "appointments People limited-disambiguation contract OK\n";
