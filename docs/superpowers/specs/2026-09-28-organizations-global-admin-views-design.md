# Organizations 1.3.0 Global Admin Views — Design

## Intent

Extend `com_xdecaroorganizations` with five global, read-only administrative views that let an administrator inspect organization relationships and governance data without opening organizations one by one.

The new views are:

1. Affiliazioni
2. Incarichi
3. Deleghe
4. Gerarchia
5. Organi

They must reuse the existing Organizations data model and domain services. They must not duplicate records, introduce cross-component hard dependencies, or move domain logic into Core.

## Release and compatibility

This is new backwards-compatible functionality, so the target release is **Organizations 1.3.0** under Semantic Versioning.

Target platform remains the component's current Joomla 6 line. Existing Organizations features, schema, organization edit tabs, maintenance, duplicates, information, backup/restore and existing ACL behavior must remain intact.

No new database tables are required. Existing tables provide all required data:

- `#__xdecaroorganizations_organizations`
- `#__xdecaroorganizations_affiliations`
- `#__xdecaroorganizations_appointments`
- `#__xdecaroorganizations_delegations`
- `#__xdecaroorganizations_bodies`

Any SQL update for 1.3.0 is therefore a no-op/version marker unless implementation uncovers a real schema requirement. A schema change must not be added merely to support listing/filtering.

## Menu

The administrator submenu becomes:

`Dashboard · Organizzazioni · Affiliazioni · Incarichi · Deleghe · Gerarchia · Organi · Duplicati · Manutenzione · Informazioni`

The five new entries are first-class administrator views declared in the component manifest and use Joomla language strings.

## Interaction model

All five pages are **global control/inspection views**, not editing surfaces.

Each row or node exposes an **Apri** action that opens the relevant existing organization edit screen. Where useful, the link includes the existing tab/section target so the administrator lands on the relevant data area.

No Add, Edit, Delete, bulk mutation, inline save or destructive action is added to these five pages.

This keeps modification logic in the existing organization edit workflows, avoids duplicating ACL and validation code, and reduces regression risk.

## Shared UI behavior

The five views use a consistent xdecaro/Joomla administrator pattern:

- page heading and short explanatory text;
- Joomla filter/search area;
- server-side search and filters;
- sortable columns where meaningful;
- pagination choices `10 / 20 / 50 / 100 / Tutte`;
- compact desktop/tablet tables;
- responsive mobile cards when a table would otherwise overflow;
- no horizontal page overflow;
- Joomla language strings only, no hardcoded translatable copy;
- light/dark mode compatible styling using existing Joomla/Core variables rather than light-only hardcoded colors;
- clear empty states;
- accessible labels, keyboard-focusable controls and links.

Filters persist through the normal Joomla list-state mechanism and reset cleanly.

## 1. Affiliazioni

### Purpose

Provide one global view of all organization-to-organization affiliation relations already stored in `#__xdecaroorganizations_affiliations`.

### Data

Each item joins:

- source organization (`organization_id`);
- target organization (`target_organization_id`);
- relation type;
- relation code;
- start/end dates;
- relation status;
- Joomla publication state.

Existing `OrganizationAffiliationDomain` label/status mapping is reused.

### Perspective switch

The page provides a compact perspective control:

- **Tutte** — show every relation normally as `Organizzazione → Ente/Federazione`;
- **Affiliazioni** — emphasize the source organization's outgoing affiliations;
- **Affiliati** — emphasize organizations affiliated to a target organization.

This is a view/presentation filter over the same records; it does not create separate affiliate data.

### Filters

- free-text search over source name/code, target name/code and relation code;
- source organization;
- target organization / ente / federation;
- relation type;
- status;
- active/current vs expired where dates permit reliable classification.

### Columns/cards

Desktop columns:

`Organizzazione | Ente/Federazione | Tipo | Stato | Periodo | Codice | Azioni`

`Apri` opens the source organization on its Affiliazioni tab. The target organization name is also navigable to its organization edit screen when the user has access.

## 2. Incarichi

### Purpose

Provide a global view of all appointments from `#__xdecaroorganizations_appointments`, allowing administrators to answer questions such as which organizations and bodies a person serves in.

### Data

Join appointments with:

- organization name/code;
- body name when present.

Person display uses the stored `person_name_snapshot` and `person_uuid`; the view does **not** require People to be installed. Existing `AppointmentDomain` is reused for role labels and visual status.

### Filters

- free-text search over person snapshot, organization, body and role/custom role;
- organization;
- body when practical without introducing an expensive global picker;
- role;
- visual status (active, planned/ended or existing domain states as defined by `AppointmentDomain`).

### Columns/cards

Desktop columns:

`Persona | Organizzazione | Organo | Ruolo | Inizio | Fine prevista/effettiva | Stato | Azioni`

`Apri` opens the organization edit screen on the relevant Incarichi/Membri governance area using the existing organization workflow.

## 3. Deleghe

### Purpose

Provide a global view of all delegations stored in `#__xdecaroorganizations_delegations`.

### Data

Join delegations with:

- parent appointment;
- person snapshot;
- role/custom role;
- body name;
- organization name/code.

Existing `OrganizationDelegationDomain` calculates effective end and visual status. `AppointmentDomain` remains the source for role labels.

### Filters

- free-text search over delegation title/scope, person and organization;
- organization;
- status;
- current/expired;
- expiring soon.

For **expiring soon**, use a deterministic server-side date window and expose the exact definition in UI help. The initial definition is `effective end within the next 30 days`, excluding already expired items.

### Columns/cards

Desktop columns:

`Delega | Persona | Organizzazione | Ruolo/Organo | Periodo | Stato | Azioni`

`Apri` opens the organization edit screen on its Deleghe area.

## 4. Gerarchia

### Purpose

Provide a global structural view of organization parent/child relationships based on `organizations.parent_id`.

### Presentation

Desktop/tablet uses an indented tree/list that remains readable for large datasets. Mobile uses stacked cards/nodes with indentation capped to avoid narrow unreadable content.

Each node shows:

- organization name;
- code when present;
- type;
- structure level;
- operational status;
- child count;
- Apri action.

### Filters/search

- search organization name/code;
- type;
- structure level;
- operational status;
- optional roots-only mode.

When search is active, matching nodes remain understandable by showing enough ancestor context to identify their location.

### Integrity diagnostics

The view is read-only but flags structural anomalies that can be derived safely from existing data:

- missing parent reference if encountered despite FK expectations/migrated legacy data;
- self-parenting;
- detected cycles;
- unreachable/orphaned nodes.

No automatic repair is performed from this page.

Cycle detection must be bounded and must never recurse indefinitely on corrupt data.

## 5. Organi

### Purpose

Provide a global view of all organization bodies from `#__xdecaroorganizations_bodies`.

### Data

Join each body with:

- organization name/code;
- parent body name when present;
- count of non-deleted appointments linked to the body.

Existing `OrganizationBodyDomain` supplies type labels and visual status.

### Filters

- free-text search over body name/code, organization and parent body;
- organization;
- body type;
- status.

### Columns/cards

Desktop columns:

`Organo | Organizzazione | Tipo | Organo superiore | Periodo | Incarichi | Stato | Azioni`

`Apri` opens the organization edit screen on its Organi area.

## MVC architecture

Each new global page receives its own administrator ListModel, HTMLView and template. The models query existing tables directly through Joomla `DatabaseInterface`/ListModel patterns, use `#__` table prefixes, quote identifiers and bind user-provided filter values.

Recommended view/model names:

- `Affiliations` / `AffiliationsModel`
- `Appointments` / `AppointmentsModel`
- `Delegations` / `DelegationsModel`
- `Hierarchy` / `HierarchyModel`
- `Bodies` / `BodiesModel`

Names deliberately distinguish these global views from the existing per-organization `OrganizationAffiliationsModel`, `OrganizationAppointmentsModel`, `OrganizationDelegationsModel` and `OrganizationBodiesModel`.

No global model may change the semantics or filtering behavior of the existing per-organization models.

Shared presentational helpers may be extracted only when at least two of the five views genuinely reuse the same logic. Do not create a new generalized service merely to avoid a few template lines.

## Data flow and navigation

1. Joomla administrator opens one of the five submenu entries.
2. The view loads list state and validates/normalizes filters.
3. Its ListModel builds a bound query against Organizations tables.
4. Existing domain services decorate rows with status/label information.
5. Template renders desktop table or mobile-card representation.
6. `Apri` routes to the existing organization edit controller/view; no write action occurs in the global page.

## ACL and security

The global pages are administrator-only and must respect the same component access boundary as the existing Organizations administrator views.

Because they are read-only:

- no new mutation ACL actions are introduced;
- no CSRF-sensitive write endpoint is added;
- all request filters are normalized and bound in SQL;
- output is escaped with Joomla view/template helpers;
- route IDs are cast to integer;
- access must not be widened beyond the current administrator component permissions.

If existing organization-specific ACL prevents opening a record, the global view must not provide a bypass. The implementation should prefer filtering inaccessible records where the current component architecture supports record-level access; otherwise the existing edit controller remains the final authorization boundary.

## Performance

Queries must avoid N+1 lookups.

- joins are used for organization/body names;
- appointment counts for Organi are aggregated in SQL rather than queried row by row;
- hierarchy data is loaded once and assembled in memory with cycle protection;
- list views use Joomla pagination unless `Tutte` is explicitly selected;
- `Tutte` must still use a reasonable hard safety ceiling if required by the current component/list conventions, and the UI must not falsely imply unlimited data if a ceiling is applied.

No live polling is required. Search/filter submission may use the established Joomla form behavior; JavaScript enhancement is optional and must not be required for correctness.

## Responsive and dark mode

At desktop widths, columns remain aligned and compact.

At mobile widths, each item becomes a card with label/value pairs and an easily tappable `Apri` action. Low-value secondary fields may move below primary fields, but data must not disappear merely because the viewport is small. Long names, role labels and codes wrap without overflow.

Colors and borders use existing Joomla/Core-compatible variables/classes. Status badges must retain sufficient contrast in light and dark mode.

## Empty/error states

Each view has a clear zero-result state that distinguishes:

- no records exist;
- current filters returned no records.

Database/query errors follow Joomla error handling and must not expose raw SQL or sensitive stack details in the UI.

Hierarchy integrity warnings are data-quality messages, not fatal exceptions.

## Testing strategy

Implementation follows TDD and adds regression coverage for each global view.

Minimum coverage:

- manifest contains the five menu entries in the agreed order;
- each global model returns records across multiple organizations rather than requiring a single organization ID;
- filters bind values and narrow results correctly;
- affiliation perspective does not duplicate or rewrite records;
- appointments work without People installed because snapshots are sufficient;
- delegation effective-end/status logic reuses the existing domain service;
- hierarchy handles roots, nested children, self-parent/cycle corruption without infinite recursion;
- body appointment counts are aggregated correctly;
- read-only pages expose no mutation task/actions;
- `Apri` routes to the correct organization;
- mobile markup/classes provide card representation without removing data;
- existing per-organization affiliation/appointment/delegation/body tests remain green;
- clean Joomla install and upgrade runtime suites remain green.

## Non-goals

This release does not:

- create/edit/delete records from the five global pages;
- duplicate affiliations as a separate affiliates table;
- add a People hard dependency;
- add new Core APIs;
- redesign the existing organization edit tabs;
- add frontend public pages for these global lists;
- repair hierarchy corruption automatically;
- create new database tables solely for reporting.

## Success criteria

Organizations 1.3.0 is complete when all five submenu entries are present, each global page can inspect real existing Organizations data with the agreed search/filter/pagination behavior, desktop and mobile presentations are usable, `Apri` navigates to the correct existing organization workflow, no write operations are exposed from the global pages, current component functionality remains unchanged, and CI/clean-install/upgrade regression suites pass.