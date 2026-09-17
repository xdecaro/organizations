# People ↔ Organizations appointment history design

Date: 2026-09-17
Status: approved design, not yet implemented
Scope: Joomla 6 only

## Goal

Show a person's Organizations appointments directly inside the People person edit screen without duplicating data and without allowing People to read Organizations private tables.

The People person screen will expose a new **Organizzazioni** tab when Organizations is installed and advertises the required public capability.

The tab contains two sections:

1. **In carica** — current appointments.
2. **Storico incarichi** — ended appointments.

Organizations remains the single owner of appointment data.

## Release boundary

The already tested package-migration releases remain frozen:

- Core 2.1.0 / `pkg_core`
- People 1.3.1 / `pkg_people`
- Organizations 1.0.26 / `pkg_organizations`

This feature is targeted to the next releases:

- Organizations 1.0.27
- People 1.3.2

This avoids changing the scope of the manually tested 1.0.26 / 1.3.1 candidates.

## Architecture

### Organizations owns the read model

Organizations adds a read-only public service dedicated to person appointment history, for example:

- service: `PersonAppointmentsService`
- component accessor: `getPersonAppointmentsService()`
- method: `getAppointmentsByPersonUuid(string $personUuid): array`

The service queries only `#__xdecaroorganizations_appointments` and `#__xdecaroorganizations_organizations` from inside Organizations. People must never query those tables directly.

Organizations advertises a Core capability:

- component: `com_xdecaroorganizations`
- capability: `organizations.people_appointments`
- capability version: `1`

### People consumes the public capability

People adds `OrganizationsIntegrationService`, following the existing `CompetitionsIntegrationService` pattern:

1. boot `com_xdecaroorganizations`;
2. verify the capability through Core `CapabilityRegistry`;
3. obtain `getPersonAppointmentsService()`;
4. request data by the current person's UUID;
5. never access Organizations tables directly.

If Organizations is not installed, does not advertise the capability, or the current user is not authorised, People continues to work normally and the Organizzazioni tab is not shown.

## Data contract

The Organizations service returns only fields required for display and navigation:

- `appointment_id`
- `appointment_uuid`
- `organization_id`
- `organization_uuid`
- `organization_name`
- `role_code`
- `role_custom`
- `role_label_key`
- `starts_on`
- `planned_ends_on`
- `ended_on`
- `end_reason`
- `visual_status`
- `is_current`

The public contract does **not** expose:

- appointment notes;
- end notes;
- organization tax/fiscal fields;
- organization address fields;
- any unrelated sensitive organization data.

People does not persist any returned appointment data.

## Current/history classification

Organizations performs classification because it owns appointment semantics.

An appointment is returned as current when the Organizations domain reports it as active/in office and there is no effective cessation. Ended appointments are returned as history. People must not reimplement appointment-state rules.

The service should reuse `AppointmentDomain` for status and role-label derivation so the People view stays consistent with the Organizations Members tab.

## Authorization

Organizations adds a narrow ACL action for this read model:

- `organizations.view_appointments`

The public appointment-history service authorises access with this action or `core.admin` on `com_xdecaroorganizations`.

This avoids requiring broad `core.manage` or `organizations.view_sensitive` merely to display a person's offices.

No permission is granted automatically by People. Joomla ACL remains authoritative.

## People UI

The new People tab label is **Organizzazioni**.

The tab appears only for an existing person with a non-empty UUID and when the Organizations capability is available and authorised.

### Section: In carica

Columns:

- Organizzazione
- Carica
- Inizio
- Fine prevista
- Stato

Example:

`Sezione Provinciale ENS Roma | Consigliere | 14/09/2026 | 17/09/2027 | In carica`

### Section: Storico incarichi

Columns:

- Organizzazione
- Carica
- Mandato
- Data cessazione
- Motivo cessazione

If either section is empty, show a concise empty-state message rather than an empty table.

### Navigation

The organization name is a link to the Organizations edit screen for that `organization_id`. The link is rendered only when the Organizations integration is available; no duplicated organization route metadata is stored in People.

Dates are formatted using Joomla/local UI conventions. Raw ISO dates remain in the provider contract.

## Language and labels

People loads the Organizations administrator language file when the integration is active so `role_label_key`, status labels, and cessation-reason labels remain consistent with Organizations.

Custom roles use `role_custom` when present; otherwise the translated `role_label_key` is used.

## Error handling

Organizations service errors are caught by `OrganizationsIntegrationService` and wrapped as an integration-unavailable error.

The People edit screen must remain usable if Organizations fails. The error is logged under `com_xdecaropeople`; the Organizzazioni tab is omitted rather than blocking person editing.

A failure in this optional integration must never prevent saving or editing a People record.

## Files expected to change

### Organizations 1.0.27

Likely areas:

- `component/admin/src/Service/CoreIntegrationService.php`
- new `component/admin/src/Service/PersonAppointmentsService.php`
- `component/admin/src/Extension/OrganizationsComponent.php`
- `component/admin/services/provider.php`
- `component/admin/access.xml`
- language files for the new ACL label if required
- tests and Joomla runtime workflow
- version/build/release metadata after implementation is proven

### People 1.3.2

Likely areas:

- new `component/admin/src/Service/OrganizationsIntegrationService.php`
- `component/admin/src/Extension/PeopleComponent.php`
- `component/admin/services/provider.php`
- `component/admin/src/View/Person/HtmlView.php`
- `component/admin/tmpl/person/edit.php`
- IT/EN language strings for tab, headings, columns, and empty states
- tests and Joomla runtime workflow
- version/build/release metadata after implementation is proven

## Testing strategy

Implementation follows TDD.

### Organizations contract tests

Verify that:

- capability `organizations.people_appointments` version 1 is registered;
- the public service exists and is exposed by the component;
- lookup is keyed by `person_uuid`;
- returned fields are allowlisted and notes/sensitive organization fields are absent;
- ACL `organizations.view_appointments` is enforced;
- current/history classification reuses the Organizations domain;
- no cross-component private table access is introduced.

### People contract tests

Verify that:

- People uses the Organizations public capability/service only;
- no `#__xdecaroorganizations_*` table name appears in People production code;
- the Organizzazioni tab is conditional;
- current and history sections render the approved columns;
- organization links point to the Organizations edit screen;
- failure or absence of Organizations does not break the person edit page.

### Cross-repository Joomla runtime

Install Joomla 6.1.3 with the candidate stack and seed:

- one person;
- one organization;
- one current appointment;
- one ended appointment for the same person.

Verify through the public service that both rows are returned in the correct classification, and verify People can render the integration while retaining its own edit/save lifecycle.

## Non-goals

This feature does not:

- let People create, edit, terminate, revoke, delete, or otherwise mutate Organizations appointments;
- duplicate appointment records in People;
- add membership data;
- merge Organizations and People domains;
- expose appointment notes or organization sensitive fields;
- change the already tested Core 2.1.0, People 1.3.1, or Organizations 1.0.26 release scope.

## Acceptance criteria

The feature is complete when an authorised user opens an existing People record and sees an **Organizzazioni** tab containing both current and historical Organizations appointments, with correct role/date/status data and a link to the organization, while People contains no direct Organizations-table dependency and continues to function when Organizations is unavailable.