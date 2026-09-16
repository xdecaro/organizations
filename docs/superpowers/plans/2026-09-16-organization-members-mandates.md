# Organization Members and Mandates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aggiungere a Organizations la gestione separata e storicizzata degli incarichi delle persone, con tab Membri, cessazioni distinte, integrazione pubblica con People e tab Sistema per UUID/creazione/modifica.

**Architecture:** Gli incarichi vivono in `#__xdecaroorganizations_appointments` e non vengono salvati insieme al record principale dell'organizzazione. `AppointmentDomain` contiene regole pure e testabili; `OrganizationAppointmentModel` gestisce persistenza/validazione server-side; `OrganizationAppointmentsModel` legge gli incarichi; `PeopleIntegrationService` usa esclusivamente `bootComponent('com_xdecaropeople')` e il `PersonProviderService` pubblico. La UI della tab Membri usa una subview dedicata e chiamate JSON separate, quindi aggiunta/modifica/cessazione non risalvano l'organizzazione.

**Tech Stack:** Joomla 6.1.x, PHP 8.1+/8.3 CI, MySQL 8, Joomla MVC/DI/WebAssetManager, JavaScript vanilla, Bootstrap modal fornita da Joomla, test PHP contract/unit già usati dal repository.

**Spec:** `docs/superpowers/specs/2026-09-16-organization-members-mandates-design.md`

## Global Constraints

- Target esclusivo: Joomla 6.1.x; non mantenere compatibilità Joomla 5.
- Release di implementazione: `1.0.21`.
- Tab organizzazione nell'ordine: Identità → Contatti → Membri → Pubblicazione → Sistema.
- `UUID`, `Creato`, `Ultima modifica` devono stare in Sistema; UUID non resta in Identità.
- Tabella incarichi: `#__xdecaroorganizations_appointments`.
- Nessuna query, model/table import o foreign key verso `#__xdecaropeople_*`.
- People è opzionale a runtime: storico leggibile da `person_name_snapshot`; nuovi incarichi richiedono People disponibile e persona risolvibile.
- Nessun limite rigido alle cariche contemporanee e nessun UNIQUE su organizzazione/persona/carica.
- `state` è solo lo stato Joomla del record, non lo stato del mandato.
- Tutte le mutazioni e la ricerca People JSON devono verificare token CSRF e ACL.
- Le date previste non vengono sovrascritte quando il mandato termina prima.
- TDD: ogni task parte da test fallente, poi implementazione minima, poi suite verde.

---

### Task 1: Schema appointments e Table Joomla

**Files:**
- Create: `tests/appointments-schema-contract.php`
- Modify: `component/admin/sql/install.mysql.utf8mb4.sql`
- Create: `component/admin/sql/updates/mysql/1.0.21.sql`
- Create: `component/admin/src/Table/OrganizationAppointmentTable.php`

**Interfaces:**
- Consumes: tabella esistente `#__xdecaroorganizations_organizations(id)`.
- Produces: tabella `#__xdecaroorganizations_appointments`; `OrganizationAppointmentTable::__construct(DatabaseDriver $db)`; `OrganizationAppointmentTable::check(): bool`.

- [ ] **Step 1: Scrivere il test schema fallente**

Creare `tests/appointments-schema-contract.php` con controlli espliciti su install SQL, migration e assenza di vincoli People:

```php
<?php
$root = dirname(__DIR__);
$install = (string) file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migrationPath = $root . '/component/admin/sql/updates/mysql/1.0.21.sql';
$migration = is_file($migrationPath) ? (string) file_get_contents($migrationPath) : '';
$tablePath = $root . '/component/admin/src/Table/OrganizationAppointmentTable.php';
$table = is_file($tablePath) ? (string) file_get_contents($tablePath) : '';

$required = [
    '#__xdecaroorganizations_appointments',
    '`person_uuid` CHAR(36) NOT NULL',
    '`person_name_snapshot` VARCHAR(255) NOT NULL',
    '`role_code` VARCHAR(50) NOT NULL',
    '`starts_on` DATE NOT NULL',
    '`planned_ends_on` DATE DEFAULT NULL',
    '`ended_on` DATE DEFAULT NULL',
    'UNIQUE KEY `idx_appointment_uuid` (`uuid`)',
    'KEY `idx_appointment_org` (`organization_id`)',
    'KEY `idx_appointment_person` (`person_uuid`)',
    'KEY `idx_appointment_org_dates` (`organization_id`,`starts_on`,`planned_ends_on`)',
    'CONSTRAINT `fk_xdecaroorganizations_appointment_org`',
    'ON DELETE CASCADE',
];

foreach ($required as $needle) {
    if (!str_contains($install, $needle) || !str_contains($migration, $needle)) {
        fwrite(STDERR, "Missing appointments schema fragment: {$needle}\n");
        exit(1);
    }
}

if (str_contains($install . $migration, '#__xdecaropeople_')) {
    fwrite(STDERR, "Appointments schema must not reference People tables.\n");
    exit(1);
}

if (!str_contains($table, "#__xdecaroorganizations_appointments")) {
    fwrite(STDERR, "OrganizationAppointmentTable must map the appointments table.\n");
    exit(1);
}

echo "appointments schema contract OK\n";
```

- [ ] **Step 2: Eseguire il test e verificare il fallimento**

Run: `php tests/appointments-schema-contract.php`

Expected: FAIL perché migration, tabella e Table class non esistono ancora.

- [ ] **Step 3: Aggiungere lo schema completo a install e migration**

Usare lo stesso `CREATE TABLE IF NOT EXISTS` in install e `1.0.21.sql`:

```sql
CREATE TABLE IF NOT EXISTS `#__xdecaroorganizations_appointments` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `uuid` CHAR(36) NOT NULL,
 `organization_id` INT UNSIGNED NOT NULL,
 `person_uuid` CHAR(36) NOT NULL,
 `person_name_snapshot` VARCHAR(255) NOT NULL,
 `role_code` VARCHAR(50) NOT NULL,
 `role_custom` VARCHAR(190) DEFAULT NULL,
 `starts_on` DATE NOT NULL,
 `planned_ends_on` DATE DEFAULT NULL,
 `ended_on` DATE DEFAULT NULL,
 `end_reason` VARCHAR(50) DEFAULT NULL,
 `end_note` TEXT DEFAULT NULL,
 `notes` TEXT DEFAULT NULL,
 `state` TINYINT NOT NULL DEFAULT 1,
 `created` DATETIME NOT NULL,
 `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
 `modified` DATETIME DEFAULT NULL,
 `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`),
 UNIQUE KEY `idx_appointment_uuid` (`uuid`),
 KEY `idx_appointment_org` (`organization_id`),
 KEY `idx_appointment_person` (`person_uuid`),
 KEY `idx_appointment_org_dates` (`organization_id`,`starts_on`,`planned_ends_on`),
 KEY `idx_appointment_state` (`state`),
 CONSTRAINT `fk_xdecaroorganizations_appointment_org`
   FOREIGN KEY (`organization_id`)
   REFERENCES `#__xdecaroorganizations_organizations` (`id`)
   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Non aggiungere UNIQUE su `organization_id/person_uuid/role_code`.

- [ ] **Step 4: Creare `OrganizationAppointmentTable`**

Implementare mapping, alias published→state e normalizzazione stringhe vuote a `null`. `check()` deve rifiutare UUID persona vuoto, snapshot vuoto, ruolo vuoto e data inizio vuota; le regole di dominio più ricche restano in `AppointmentDomain` del Task 2.

- [ ] **Step 5: Eseguire test e syntax check**

Run: `php tests/appointments-schema-contract.php && php -l component/admin/src/Table/OrganizationAppointmentTable.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/appointments-schema-contract.php component/admin/sql/install.mysql.utf8mb4.sql component/admin/sql/updates/mysql/1.0.21.sql component/admin/src/Table/OrganizationAppointmentTable.php
git commit -m "feat: add organization appointments schema"
```

---

### Task 2: Regole pure di mandato, durata e stato visuale

**Files:**
- Create: `tests/appointments-domain.php`
- Create: `component/admin/src/Service/AppointmentDomain.php`

**Interfaces:**
- Produces: `AppointmentDomain::roles(): array`, `endReasons(): array`, `plannedEnd(string $startsOn, ?int $years, ?string $provided): ?string`, `validate(array $data): array`, `status(array $appointment, ?DateTimeImmutable $today = null): string`, `roleLabelKey(string $roleCode): string`.
- Status canonici prodotti: `active`, `expired`, `ended`, `resigned`, `revoked`, `forfeited`.

- [ ] **Step 1: Scrivere unit test fallente**

`tests/appointments-domain.php` deve definire `_JEXEC`, caricare la classe e verificare almeno:

```php
<?php
define('_JEXEC', 1);
require dirname(__DIR__) . '/component/admin/src/Service/AppointmentDomain.php';

use xdecaro\Component\Organizations\Administrator\Service\AppointmentDomain;

$assert = static function (bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, $message . "\n"); exit(1); }
};

$assert(AppointmentDomain::plannedEnd('2026-09-16', 1, null) === '2027-09-16', '1 year duration failed');
$assert(AppointmentDomain::plannedEnd('2026-09-16', 5, null) === '2031-09-16', '5 year duration failed');
$assert(AppointmentDomain::plannedEnd('2026-09-16', 3, '2029-10-01') === '2029-10-01', 'manual override must win');
$assert(AppointmentDomain::plannedEnd('2026-09-16', null, '2028-03-16') === '2028-03-16', 'custom date must be preserved');

$today = new DateTimeImmutable('2026-09-16');
$assert(AppointmentDomain::status(['planned_ends_on' => '2026-09-16', 'ended_on' => null, 'end_reason' => null], $today) === 'active', 'appointment ending today is active');
$assert(AppointmentDomain::status(['planned_ends_on' => '2026-09-15', 'ended_on' => null, 'end_reason' => null], $today) === 'expired', 'past appointment must expire');
$assert(AppointmentDomain::status(['end_reason' => 'term_end', 'ended_on' => '2026-09-10'], $today) === 'ended', 'term_end mapping failed');
$assert(AppointmentDomain::status(['end_reason' => 'resignation', 'ended_on' => '2026-09-10'], $today) === 'resigned', 'resignation mapping failed');
$assert(AppointmentDomain::status(['end_reason' => 'revocation', 'ended_on' => '2026-09-10'], $today) === 'revoked', 'revocation mapping failed');
$assert(AppointmentDomain::status(['end_reason' => 'forfeiture', 'ended_on' => '2026-09-10'], $today) === 'forfeited', 'forfeiture mapping failed');

$errors = AppointmentDomain::validate([
    'person_uuid' => 'bad', 'role_code' => 'custom', 'role_custom' => '',
    'starts_on' => '2026-09-16', 'planned_ends_on' => '2026-09-15',
]);
$assert($errors !== [], 'invalid UUID/custom role/date ordering must fail');

echo "appointments domain OK\n";
```

- [ ] **Step 2: Eseguire e verificare il fallimento**

Run: `php tests/appointments-domain.php`

Expected: FAIL perché `AppointmentDomain.php` non esiste.

- [ ] **Step 3: Implementare il dominio minimo**

La classe deve avere cataloghi costanti:

```php
private const ROLES = ['president','vice_president','secretary','treasurer','councillor','auditor','director','coordinator','custom'];
private const END_REASONS = ['term_end','resignation','revocation','forfeiture','other'];
```

`plannedEnd()` deve preservare sempre `provided` valido; solo se non fornito calcola `+$years years` per 1–5. `validate()` deve verificare UUID v1–v5, ruolo, custom, date ISO `Y-m-d`, ordine date e coerenza `ended_on/end_reason`. `status()` deve usare prima `end_reason`, poi la scadenza prevista.

- [ ] **Step 4: Eseguire unit test**

Run: `php tests/appointments-domain.php && php -l component/admin/src/Service/AppointmentDomain.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/appointments-domain.php component/admin/src/Service/AppointmentDomain.php
git commit -m "feat: add appointment domain rules"
```

---

### Task 3: Adapter pubblico People con fallback snapshot

**Files:**
- Create: `tests/appointments-people-contract.php`
- Create: `component/admin/src/Service/PeopleIntegrationService.php`
- Modify: `component/admin/services/provider.php`
- Modify: `component/admin/src/Extension/OrganizationsComponent.php`

**Interfaces:**
- Produces: `PeopleIntegrationService::isAvailable(): bool`, `searchPeople(string $search, int $limit = 20): array`, `getPerson(string $uuid): ?array`, `getPeopleByUuids(array $uuids): array`.
- OrganizationsComponent espone `getPeopleIntegrationService(): PeopleIntegrationService`.
- Consumes il provider pubblico People tramite `bootComponent('com_xdecaropeople')->getPersonProviderService()`; nessun import di model/table People.

- [ ] **Step 1: Scrivere contract test fallente**

Il test deve verificare che il nuovo adapter contenga `bootComponent('com_xdecaropeople')`, `getPersonProviderService`, `searchPeople`, `getPerson`, `getPeopleByUuids`, e che tutto `component/` continui a non contenere `#__xdecaropeople_`.

- [ ] **Step 2: Eseguire e verificare il fallimento**

Run: `php tests/appointments-people-contract.php`

Expected: FAIL perché l'adapter non esiste.

- [ ] **Step 3: Implementare `PeopleIntegrationService`**

Usare un provider opzionale memorizzato in cache:

```php
private ?object $provider = null;
private bool $resolved = false;

private function provider(): ?object
{
    if ($this->resolved) {
        return $this->provider;
    }
    $this->resolved = true;

    try {
        $component = Factory::getApplication()->bootComponent('com_xdecaropeople');
        if (!method_exists($component, 'getPersonProviderService')) {
            return null;
        }
        $provider = $component->getPersonProviderService();
        if (!is_object($provider)) {
            return null;
        }
        return $this->provider = $provider;
    } catch (Throwable) {
        return null;
    }
}
```

`searchPeople()` chiama `$provider->searchPeople(['search' => $search], $limit, false)`. `getPerson()` e batch usano i metodi pubblici omonimi con `sensitive=false`. Se People manca, restituire `[]`/`null`, senza eccezioni nella lettura dello storico.

- [ ] **Step 4: Registrare il servizio nel DI Organizations**

Condividere `PeopleIntegrationService::class` in `services/provider.php`, aggiungere setter/getter tipizzati a `OrganizationsComponent`, e impostarlo durante costruzione del componente.

- [ ] **Step 5: Eseguire test**

Run: `php tests/appointments-people-contract.php && php tests/core-integration-smoke.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/appointments-people-contract.php component/admin/src/Service/PeopleIntegrationService.php component/admin/services/provider.php component/admin/src/Extension/OrganizationsComponent.php
git commit -m "feat: integrate appointments with People provider"
```

---

### Task 4: CRUD separato degli incarichi e cessazioni

**Files:**
- Create: `tests/appointments-backend-contract.php`
- Create: `component/admin/src/Model/OrganizationAppointmentModel.php`
- Create: `component/admin/src/Model/OrganizationAppointmentsModel.php`
- Create: `component/admin/src/Controller/AppointmentController.php`

**Interfaces:**
- `OrganizationAppointmentModel::saveAppointment(array $data): int`
- `OrganizationAppointmentModel::endAppointment(int $id, string $reason, string $endedOn, ?string $note): bool`
- `OrganizationAppointmentsModel::setOrganizationId(int $organizationId): void`
- `OrganizationAppointmentsModel::getItems(): array`
- JSON tasks: `appointment.searchPeople`, `appointment.save`, `appointment.end`.

- [ ] **Step 1: Scrivere contract test fallente**

Verificare nei sorgenti che:
- `saveAppointment` e `endAppointment` esistano;
- `planned_ends_on` non venga modificato dentro `endAppointment`;
- il salvataggio nuovo risolva `person_uuid` con `PeopleIntegrationService::getPerson()` e ricavi `person_name_snapshot` da `display_name` con fallback `preferred_name`/nome+cognome;
- update di un incarico esistente con stesso `person_uuid` possa conservare lo snapshot anche se People non è disponibile;
- non esista controllo che blocchi cariche sovrapposte;
- controller usi `Session::checkToken('post')` e ACL `core.create`/`core.edit`;
- controller restituisca `JsonResponse`.

- [ ] **Step 2: Eseguire e verificare il fallimento**

Run: `php tests/appointments-backend-contract.php`

Expected: FAIL perché model/controller non esistono.

- [ ] **Step 3: Implementare `OrganizationAppointmentModel`**

Il model deve:
1. caricare il record esistente quando `id > 0`;
2. verificare che `organization_id` esista nella tabella Organizations;
3. passare i dati a `AppointmentDomain::validate()`;
4. per nuovo record o cambio persona richiedere People disponibile e persona risolvibile;
5. generare snapshot server-side, mai fidarsi di `person_name_snapshot` inviato dal browser;
6. generare UUID v4 e audit fields su create/update;
7. salvare tramite `OrganizationAppointmentTable`;
8. non controllare/impedire sovrapposizioni.

`endAppointment()` deve modificare esclusivamente `ended_on`, `end_reason`, `end_note`, `modified`, `modified_by`, dopo validazione di reason/data rispetto a `starts_on`.

- [ ] **Step 4: Implementare `OrganizationAppointmentsModel`**

Query solo `#__xdecaroorganizations_appointments`, filtrata per `organization_id` e `state >= 0`, ordinata per `role_code`, `person_name_snapshot`, `starts_on`. Per ogni riga aggiungere `visual_status = AppointmentDomain::status($row)` e `role_label_key = AppointmentDomain::roleLabelKey($row['role_code'])`.

- [ ] **Step 5: Implementare `AppointmentController` JSON**

Pattern per ogni azione mutante/ricerca:

```php
if (!Session::checkToken('post')) {
    $this->respond(null, Text::_('JINVALID_TOKEN'), true);
    return;
}
```

- `searchPeople`: richiede `core.manage` Organizations, limita testo e risultati, restituisce solo `uuid` + nome visualizzato.
- `save`: `core.create` per nuovo, `core.edit` per modifica; passa input filtrato al model.
- `end`: `core.edit`; passa id/reason/date/note al model.
- `respond`: `echo new JsonResponse($data, $message, $error); Factory::getApplication()->close();`.

- [ ] **Step 6: Eseguire test backend**

Run: `php tests/appointments-backend-contract.php && find component/admin/src/Controller component/admin/src/Model component/admin/src/Table component/admin/src/Service -type f -name '*.php' -print0 | xargs -0 -n1 php -l`

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/appointments-backend-contract.php component/admin/src/Model/OrganizationAppointmentModel.php component/admin/src/Model/OrganizationAppointmentsModel.php component/admin/src/Controller/AppointmentController.php
git commit -m "feat: add appointment CRUD and termination actions"
```

---

### Task 5: Tab Sistema e UI Membri con modali

**Files:**
- Create: `tests/appointments-ui-contract.php`
- Modify: `component/admin/forms/organization.xml`
- Modify: `component/admin/src/View/Organization/HtmlView.php`
- Modify: `component/admin/tmpl/organization/edit.php`
- Create: `component/admin/tmpl/organization/edit_members.php`
- Modify: `component/media/js/organization-edit.js`
- Modify: `component/media/css/admin.css`
- Modify: `component/admin/language/it-IT/com_xdecaroorganizations.ini`
- Modify: `component/admin/language/en-GB/com_xdecaroorganizations.ini`

**Interfaces:**
- View properties: `$appointments`, `$peopleAvailable`, `$canEditAppointments`.
- DOM hooks: `[data-appointment-add]`, `[data-appointment-edit]`, `[data-appointment-end]`, `[data-people-search]`, `[data-duration-years]`.
- Form fields JSON sono separati dal `jform` principale e inviati ai task `appointment.*`.

- [ ] **Step 1: Scrivere UI contract fallente**

Il test deve verificare:
- `organization.xml`: `uuid`, `created`, `modified` dentro fieldset `system`; UUID non nel fieldset `identity`;
- `edit.php`: tab order identity → contacts → members → publishing → system;
- `edit_members.php`: sezioni “In carica” e “Storico”, messaggio per record non salvato, pulsante aggiungi, modale incarico e modale cessazione;
- JS: ricerca People incrementale, CSRF token, calcolo 1–5 anni, `window.location.reload()` dopo successo;
- View: carica appuntamenti soltanto per item già salvato e determina People disponibile senza impedire il rendering.

- [ ] **Step 2: Eseguire e verificare il fallimento**

Run: `php tests/appointments-ui-contract.php`

Expected: FAIL perché Sistema/Membri non sono ancora presenti.

- [ ] **Step 3: Spostare campi tecnici in Sistema**

`organization.xml` deve terminare con:

```xml
<fieldset name="system" label="COM_XDECAROORGANIZATIONS_FIELDSET_SYSTEM">
  <field name="uuid" type="text" label="COM_XDECAROORGANIZATIONS_FIELD_UUID" readonly="true"/>
  <field name="created" type="text" label="JGLOBAL_CREATED_DATE" readonly="true"/>
  <field name="modified" type="text" label="JGLOBAL_FIELD_MODIFIED_LABEL" readonly="true"/>
</fieldset>
```

Rimuovere `uuid` da identity; non modificare persistenza dell'organizzazione.

- [ ] **Step 4: Preparare dati Membri nella View**

Per item salvato, creare `OrganizationAppointmentsModel` tramite MVC factory, impostare `organization_id` e leggere gli items. Ottenere `PeopleIntegrationService` dal componente Organizations e chiamare `isAvailable()` dentro `try/catch`; se People manca, `$peopleAvailable=false` e la pagina resta leggibile.

- [ ] **Step 5: Rendere le cinque tab nell'ordine approvato**

In `edit.php`, fra Contatti e Pubblicazione:

```php
echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'members', Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_MEMBERS'));
echo $this->loadTemplate('members');
echo HTMLHelper::_('uitab.endTab');
```

Aggiungere Sistema dopo Pubblicazione con `$this->form->renderFieldset('system')`.

- [ ] **Step 6: Creare `edit_members.php`**

Separare `$active` e `$history` in base a `visual_status`; mostrare snapshot come nome principale. Per organizzazione nuova mostrare solo messaggio di primo salvataggio. Se People indisponibile, mostrare storico normalmente e disabilitare Aggiungi con messaggio. Ogni riga attiva espone azioni Modifica, Fine mandato, Dimissioni, Revoca, Decadenza; storico conserva Modifica amministrativa. Usare output escaped.

- [ ] **Step 7: Implementare JavaScript della modale**

Estendere `organization-edit.js` senza rompere l'autoselezione lingua esistente:
- debounce ricerca persona 250 ms;
- POST `task=appointment.searchPeople` con token Joomla;
- selezione salva UUID in hidden field e nome solo per display;
- cambio durata 1–5 calcola `planned_ends_on`; il campo resta modificabile manualmente;
- `custom` non sovrascrive la data manuale;
- save/end fanno POST ai rispettivi task e ricaricano la pagina su successo;
- `role_code=custom` mostra/rende required `role_custom`.

- [ ] **Step 8: Aggiungere CSS e stringhe IT/EN**

Aggiungere solo stili scoped sotto `.xdecaro-organizations-organization-edit`; tradurre ruoli, stati, pulsanti, messaggi People non disponibile, primo salvataggio e label cessazioni.

- [ ] **Step 9: Eseguire UI e regressioni esistenti**

Run:

```bash
php tests/appointments-ui-contract.php
php tests/contacts-ux-contract.php
php tests/edit-name-heading-contract.php
php tests/form-validation-contract.php
```

Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add tests/appointments-ui-contract.php component/admin/forms/organization.xml component/admin/src/View/Organization/HtmlView.php component/admin/tmpl/organization/edit.php component/admin/tmpl/organization/edit_members.php component/media/js/organization-edit.js component/media/css/admin.css component/admin/language/it-IT/com_xdecaroorganizations.ini component/admin/language/en-GB/com_xdecaroorganizations.ini
git commit -m "feat: add members and system organization tabs"
```

---

### Task 6: Release 1.0.21, Joomla 6-only CI e verifica runtime

**Files:**
- Modify: `VERSION`
- Modify: `component/xdecaroorganizations.xml`
- Modify: `package/pkg_xdecaroorganizations.xml`
- Modify: `component/media/joomla.asset.json`
- Modify: `.github/workflows/build.yml`
- Modify: `updates/pkg_xdecaroorganizations.xml` solo nel normale flusso di release quando SHA256 del pacchetto è disponibile; non inventare hash.

**Interfaces:**
- Versione sorgente: `1.0.21`.
- Manifest target: Joomla `6.*`.
- Runtime CI: Joomla `6.1.3` (o successiva 6.1.x verificata esplicitamente nello stesso change).
- CI verifica esistenza sia di `org_xdecaroorganizations_organizations` sia di `org_xdecaroorganizations_appointments`.

- [ ] **Step 1: Estendere i test/CI prima del bump**

In `.github/workflows/build.yml` aggiungere i nuovi test alla fase `Validate source`:

```bash
php tests/appointments-schema-contract.php
php tests/appointments-domain.php
php tests/appointments-people-contract.php
php tests/appointments-backend-contract.php
php tests/appointments-ui-contract.php
```

Rimuovere Joomla 5.4.8 dalla matrix runtime e il ramo di download relativo; mantenere Joomla 6.1.x. Dopo install aggiungere:

```bash
test "$("${MYSQL[@]}" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='joomla' AND table_name='org_xdecaroorganizations_appointments';")" = 1
```

- [ ] **Step 2: Aggiornare il target Joomla nei manifest**

In componente/package/update metadata sostituire `(5|6).*` con `6.*`/pattern equivalente Joomla 6. Non cambiare PHP minimo se la pipeline continua a validare PHP 8.1 e 8.3.

- [ ] **Step 3: Portare la versione sorgente a 1.0.21**

Aggiornare `VERSION`, manifest componente/package e `joomla.asset.json`. Il build continuerà a sincronizzare la versione asset dal file `VERSION`.

- [ ] **Step 4: Eseguire l'intera suite locale disponibile**

Run:

```bash
find component package tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/core-integration-smoke.php
php tests/joomla6-listmodel-contract.php
php tests/form-controller-option-contract.php
php tests/form-validation-contract.php
php tests/trashed-filter-contract.php
php tests/information-version-contract.php
php tests/state-actions-contract.php
php tests/admin-ux-duplicates-contract.php
php tests/no-delete-toolbar-contract.php
php tests/parent-self-option-contract.php
php tests/edit-name-heading-contract.php
php tests/contacts-ux-contract.php
php tests/hierarchy-list-contract.php
php tests/appointments-schema-contract.php
php tests/appointments-domain.php
php tests/appointments-people-contract.php
php tests/appointments-backend-contract.php
php tests/appointments-ui-contract.php
```

Expected: tutti PASS.

- [ ] **Step 5: Verificare il divieto cross-component**

Run:

```bash
! grep -RniE '#__(xdecaropeople|decarocourses|decarodocuments|decaromembership|xdecarocompetitions)_' component
```

Expected: exit 0 senza match.

- [ ] **Step 6: Build deterministico**

Run:

```bash
chmod +x build/build.sh
build/build.sh
cp dist/SHA256SUMS.txt /tmp/organizations-sums
build/build.sh
diff -u /tmp/organizations-sums dist/SHA256SUMS.txt
for z in dist/*.zip; do unzip -t "$z" >/dev/null; done
```

Expected: nessuna differenza, ZIP validi.

- [ ] **Step 7: Commit della release source**

```bash
git add VERSION component/xdecaroorganizations.xml package/pkg_xdecaroorganizations.xml component/media/joomla.asset.json .github/workflows/build.yml
git commit -m "chore: prepare Organizations 1.0.21"
```

- [ ] **Step 8: Verifica finale su GitHub Actions**

Push del ramo implementativo e controllo che `validate` e `joomla-runtime` siano verdi su Joomla 6.1.x. L'update feed va aggiornato dal normale workflow di release con lo SHA256 reale del pacchetto, non manualmente con un valore stimato.

---

## Self-review coverage

- Schema e indici: Task 1.
- UUID/Creato/Ultima modifica in Sistema: Task 5.
- Tab Membri e modali: Task 5.
- Durate 1–5/personalizzata: Task 2 + Task 5.
- Cariche contemporanee senza limite rigido: Task 1 + Task 4.
- Fine mandato/dimissioni/revoca/decadenza senza perdere `planned_ends_on`: Task 2 + Task 4.
- Stati visuali derivati: Task 2.
- Integrazione People solo provider pubblico: Task 3.
- Snapshot e fallback People assente: Task 3 + Task 4 + Task 5.
- CSRF/ACL: Task 4.
- Joomla 6.1.x e pipeline completa: Task 6.
- Nessun placeholder funzionale o requisito rinviato necessario ai criteri di accettazione.
