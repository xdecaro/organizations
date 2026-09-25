# People ↔ Organizations appointment history design

Date: 2026-09-17
Status: approved
Scope: Joomla 6 only

## Goal

Show a person's Organizations appointments inside the People person edit screen without duplicating data and without allowing People to read Organizations private tables.

People will expose an **Organizzazioni** tab when Organizations is installed, authorised, and advertises the required public capability. The tab contains **In carica** and **Storico incarichi**.

## Release boundary

The already tested package-migration releases remain frozen:
- Core 2.1.0 / `pkg_core`
- People 1.3.1 / `pkg_people`
- Organizations 1.0.26 / `pkg_organizations`

This feature targets:
- Organizations 1.0.27
- People 1.3.2

## Architecture

Organizations remains the single owner of appointment data. It adds a read-only `PersonAppointmentsService` exposed through `getPersonAppointmentsService()` and capability `organizations.people_appointments` version `1`.

The public method is `getAppointmentsByPersonUuid(string $personUuid): array`. It returns only `appointment_id`, `appointment_uuid`, `organization_id`, `organization_uuid`, `organization_name`, `role_code`, `role_custom`, `role_label_key`, `starts_on`, `planned_ends_on`, `ended_on`, `end_reason`, `visual_status`, and `is_current`.

It never returns appointment notes, end notes, fiscal/address organization fields, or unrelated sensitive data. Classification and labels reuse `AppointmentDomain`.

Organizations adds ACL action `organizations.view_appointments`; `core.admin` is also accepted.

People adds `OrganizationsIntegrationService`, following the existing Competitions integration pattern: boot Organizations, verify the Core capability, obtain the public service, and query by person UUID. People never queries `#__xdecaroorganizations_*` directly and never persists appointment data.

If Organizations is missing, incompatible, unauthorised, or fails, People remains usable and omits the Organizzazioni tab.

## People UI

### In carica
Columns: Organizzazione, Carica, Inizio, Fine prevista, Stato.

### Storico incarichi
Columns: Organizzazione, Carica, Mandato, Data cessazione, Motivo cessazione.

The organization name links to the Organizations edit screen. Dates are formatted for the UI while the provider contract retains ISO dates. People loads Organizations language strings so roles/status/end reasons remain consistent.

## Non-goals

People cannot create/edit/end/delete Organizations appointments. No appointment duplication, membership merge, sensitive organization fields, or direct Organizations-table dependency is introduced.

## Acceptance criteria

An authorised user opening an existing People record sees an **Organizzazioni** tab with current and historical Organizations appointments, correct role/date/status data and organization navigation. People remains functional without Organizations and contains no direct Organizations-table access.