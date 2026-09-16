<?php

$root = dirname(__DIR__);
$service = (string) file_get_contents($root . '/component/admin/src/Service/PeopleIntegrationService.php');
$controller = (string) file_get_contents($root . '/component/admin/src/Controller/AppointmentController.php');
$js = (string) file_get_contents($root . '/component/media/js/organization-edit.js');

if (!preg_match('/searchPeople\(\s*\[\'search\' => trim\(\$search\)\],\s*\$limit,\s*true\s*\)/s', $service)) {
    fwrite(STDERR, "People search must request sensitive data first so authorised users can disambiguate homonyms by birth data.\n");
    exit(1);
}

if (!preg_match('/catch \(Throwable\).*?searchPeople\(\s*\[\'search\' => trim\(\$search\)\],\s*\$limit,\s*false\s*\)/s', $service)) {
    fwrite(STDERR, "People search must fall back to the public profile when sensitive People data is not authorised.\n");
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

echo "appointments People disambiguation contract OK\n";
