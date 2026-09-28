# Organizations 1.3.0 Global Admin Views Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add five global read-only administrator views — Affiliazioni, Incarichi, Deleghe, Gerarchia and Organi — to Organizations 1.3.0, reusing the existing Organizations domain tables/services and preserving all existing edit workflows.

**Architecture:** Each feature is a first-class Joomla administrator `ListModel` + `HTMLView` + template. Global list models query the existing Organizations tables directly, bind every request filter, reuse the existing domain services for labels/status, and never mutate data. A shared admin stylesheet provides compact desktop tables, responsive mobile cards and dark-mode-safe presentation without changing the existing per-organization screens.

**Tech Stack:** Joomla 6.1.3, PHP 8.1+, Joomla MVC/ListModel, DatabaseInterface/query builder, Joomla Language/Route/Pagination, Bootstrap/Atum admin classes, existing xdecaro Organizations domain services.

**Spec:** `docs/superpowers/specs/2026-09-28-organizations-global-admin-views-design.md`

## Global Constraints

- Release target is exactly `1.3.0`.
- Joomla target remains the component's current Joomla 6 line.
- No new database tables solely for these views.
- No hard dependency on People; appointments use `person_name_snapshot` and `person_uuid` already stored by Organizations.
- All five pages are read-only; no Add/Edit/Delete/bulk mutation tasks are exposed.
- Existing organization edit tabs and write flows remain the only mutation surfaces.
- Use `#__` table prefixes, quoted identifiers, bound filter values and escaped output.
- Existing per-organization models must keep their current semantics.
- Desktop/tablet use compact tables; mobile uses cards without horizontal page overflow.
- Language strings are Joomla language keys; no new translatable hardcoded copy.
- Light/dark mode must remain legible without light-only hardcoded surfaces.
- Pagination choices are `10 / 20 / 50 / 100 / Tutte`; if `Tutte` needs a safety ceiling, the UI must state it.

## Review Focus

- Corrupt hierarchy data: self-parent/cycle/orphan input must render diagnostics and terminate without recursion loops; covered in Task 5.
- Missing People extension: Incarichi must still render person snapshots and never query People as a required dependency; covered in Task 3.
- Empty or invalid request filters: models must normalize/ignore unsupported values and never interpolate them into SQL; covered in Tasks 2–6.
- Large result sets with `Tutte`: the list must not silently imply unlimited rows if a safety ceiling is applied; covered in Task 1 and each list model test.
- Record navigation: every `Apri` link must cast IDs to integer and land on the correct existing organization/tab without creating a write bypass; covered in Tasks 2–6.

---

### Task 1: Shared menu, list-view shell and responsive admin presentation

**Files:**
- Modify: `component/xdecaroorganizations.xml`
- Modify: `component/admin/language/en-GB/com_xdecaroorganizations.ini`
- Modify: `component/admin/language/en-GB/com_xdecaroorganizations.sys.ini`
- Modify: `component/admin/language/it-IT/com_xdecaroorganizations.ini`
- Modify: `component/admin/language/it-IT/com_xdecaroorganizations.sys.ini`
- Create: `component/media/css/global-lists.css`
- Modify: `component/media/joomla.asset.json`
- Create: `tests/global-admin-menu-contract.php`
- Create: `tests/global-admin-readonly-responsive-contract.php`

**Interfaces:**
- Consumes: existing component manifest submenu and current Organizations administrator styling conventions.
- Produces: submenu views `affiliations`, `appointments`, `delegations`, `hierarchy`, `bodies`; shared CSS asset `com_xdecaroorganizations.global-lists`.

- [ ] **Step 1: Write the failing menu/read-only/responsive contract tests**

Assert exact submenu order:
`dashboard, organizations, affiliations, appointments, delegations, hierarchy, bodies, duplicates, maintenance, information`.
Assert shared CSS contains desktop table and mobile-card selectors, uses a mobile media query, and contains no mutation-action selectors/tasks.

- [ ] **Step 2: Run the new tests and verify RED**

Run:
`php tests/global-admin-menu-contract.php && php tests/global-admin-readonly-responsive-contract.php`
Expected: FAIL because the five views/assets do not yet exist.

- [ ] **Step 3: Add submenu language keys and shared CSS asset registration**

Use Joomla language keys for all five menu labels and generic list UI strings (`Apri`, filters, no records, no filtered results, Tutte, status/date labels). Register only the new shared stylesheet; do not alter existing maintenance/mobile CSS.

- [ ] **Step 4: Run the Task 1 tests and verify GREEN**

Run the two PHP contract tests above.
Expected: PASS.

- [ ] **Step 5: Commit**

Commit message: `feat: scaffold global organization admin views`

---

### Task 2: Global Affiliazioni view

**Files:**
- Create: `component/admin/src/Model/AffiliationsModel.php`
- Create: `component/admin/src/View/Affiliations/HtmlView.php`
- Create: `component/admin/tmpl/affiliations/default.php`
- Modify: language files from Task 1
- Create: `tests/global-affiliations-model-contract.php`
- Create: `tests/global-affiliations-ui-contract.php`

**Interfaces:**
- Consumes: `#__xdecaroorganizations_affiliations`, source/target organizations, `OrganizationAffiliationDomain`.
- Produces: global rows with source/target names/codes, relation type/status labels, current/expired classification and organization edit routes.

- [ ] **Step 1: Write failing model tests**

Assert the model:
- has no required single `organizationId` setter;
- joins both source and target organizations;
- searches source/target name/code plus relation code using bound placeholders;
- accepts only allowed relation/status/current filters;
- supports perspective values `all`, `affiliations`, `affiliates` without duplicating rows;
- orders only through an allow-list;
- returns global results across multiple organizations.

- [ ] **Step 2: Run model test and verify RED**

Run: `php tests/global-affiliations-model-contract.php`
Expected: FAIL because `AffiliationsModel` does not exist.

- [ ] **Step 3: Implement `AffiliationsModel extends ListModel`**

Use `populateState()` for search/source/target/type/status/current/perspective. Bind all request-derived query values. Reuse `OrganizationAffiliationDomain::typeLabelKey()` and `statusLabelKey()` in `getItems()`.

- [ ] **Step 4: Write failing UI contract**

Assert columns/cards expose `Organizzazione`, `Ente/Federazione`, `Tipo`, `Stato`, `Periodo`, `Codice`, `Azioni`; perspective switch contains `Tutte`, `Affiliazioni`, `Affiliati`; source `Apri` routes to organization edit with the affiliations section; target name is navigable; no mutation task/buttons are present.

- [ ] **Step 5: Implement `View/Affiliations/HtmlView.php` and template**

Load items/state/pagination, add title, load `com_xdecaroorganizations.global-lists`, render desktop table + mobile cards and accessible filters.

- [ ] **Step 6: Run affiliation tests and existing per-organization affiliation tests**

Run:
`php tests/global-affiliations-model-contract.php`
`php tests/global-affiliations-ui-contract.php`
`php tests/affiliations-contract.php`
`php tests/affiliation-direction-contract.php`
Expected: PASS.

- [ ] **Step 7: Commit**

Commit message: `feat: add global affiliations view`

---

### Task 3: Global Incarichi view

**Files:**
- Create: `component/admin/src/Model/AppointmentsModel.php`
- Create: `component/admin/src/View/Appointments/HtmlView.php`
- Create: `component/admin/tmpl/appointments/default.php`
- Modify: language files from Task 1
- Create: `tests/global-appointments-model-contract.php`
- Create: `tests/global-appointments-ui-contract.php`

**Interfaces:**
- Consumes: `#__xdecaroorganizations_appointments`, organizations, bodies, `AppointmentDomain`.
- Produces: person snapshot + organization/body/role/date/status rows without requiring People.

- [ ] **Step 1: Write failing model contract**

Assert joins organization/body, searches `person_name_snapshot`, organization/body, `role_code`/`role_custom`, binds filters, decorates each row with `AppointmentDomain::status()` and `roleLabelKey()`, and contains no People table/component dependency.

- [ ] **Step 2: Run and verify RED**

Run: `php tests/global-appointments-model-contract.php`
Expected: FAIL.

- [ ] **Step 3: Implement `AppointmentsModel extends ListModel`**

Support search, organization, body, role and visual-status filters. Derive display status through the existing domain service after query retrieval rather than inventing a parallel status definition.

- [ ] **Step 4: Write failing UI contract**

Assert columns/cards: `Persona | Organizzazione | Organo | Ruolo | Inizio | Fine prevista/effettiva | Stato | Azioni`; person snapshot is rendered when People is absent; `Apri` points to the existing organization governance/incarichi area; no write controls.

- [ ] **Step 5: Implement view/template and language strings**

Reuse shared list styling and Joomla pagination; long person/role names wrap on mobile.

- [ ] **Step 6: Run global + existing appointment tests**

Run:
`php tests/global-appointments-model-contract.php`
`php tests/global-appointments-ui-contract.php`
`php tests/appointments-backend-contract.php`
`php tests/appointments-domain.php`
`php tests/appointments-people-contract.php`
Expected: PASS.

- [ ] **Step 7: Commit**

Commit message: `feat: add global appointments view`

---

### Task 4: Global Deleghe view

**Files:**
- Create: `component/admin/src/Model/DelegationsModel.php`
- Create: `component/admin/src/View/Delegations/HtmlView.php`
- Create: `component/admin/tmpl/delegations/default.php`
- Modify: language files from Task 1
- Create: `tests/global-delegations-model-contract.php`
- Create: `tests/global-delegations-ui-contract.php`

**Interfaces:**
- Consumes: delegations + parent appointment + body + organization, `OrganizationDelegationDomain`, `AppointmentDomain`.
- Produces: effective-end, visual status and 30-day expiring-soon classification.

- [ ] **Step 1: Write failing model contract**

Assert one joined query supplies delegation, person snapshot, role/body and organization. Search/filter values are bound. `expiring_soon` means effective end from today through today+30 days inclusive, excluding already expired rows. Domain status/effective-end methods are reused.

- [ ] **Step 2: Run and verify RED**

Run: `php tests/global-delegations-model-contract.php`
Expected: FAIL.

- [ ] **Step 3: Implement `DelegationsModel extends ListModel`**

Support search, organization, status/current/expired/expiring-soon filters. Keep the 30-day window deterministic from Joomla's current date source.

- [ ] **Step 4: Write failing UI contract and implement view/template**

Assert columns/cards: `Delega | Persona | Organizzazione | Ruolo/Organo | Periodo | Stato | Azioni`; expiring-soon filter help explicitly says 30 days; `Apri` routes to Deleghe area; no mutation controls.

- [ ] **Step 5: Run global + existing delegation tests**

Run the two new contracts plus the repository's existing delegation/domain contracts.
Expected: PASS.

- [ ] **Step 6: Commit**

Commit message: `feat: add global delegations view`

---

### Task 5: Global Gerarchia view with bounded integrity diagnostics

**Files:**
- Create: `component/admin/src/Model/HierarchyModel.php`
- Create: `component/admin/src/View/Hierarchy/HtmlView.php`
- Create: `component/admin/tmpl/hierarchy/default.php`
- Modify: language files from Task 1
- Create: `tests/global-hierarchy-model-contract.php`
- Create: `tests/global-hierarchy-ui-contract.php`

**Interfaces:**
- Consumes: `#__xdecaroorganizations_organizations.parent_id` and existing organization labels/status fields.
- Produces: ordered tree nodes with `depth`, `child_count`, ancestor context and anomaly flags (`missing_parent`, `self_parent`, `cycle`, `orphan`).

- [ ] **Step 1: Write failing hierarchy algorithm contract**

Fixture cases must cover root → child → grandchild, self-parent, two-node cycle, missing parent and disconnected/unreachable node. Assert traversal terminates, every input organization appears at most once in the rendered result, and cycles are flagged instead of recursed.

- [ ] **Step 2: Run and verify RED**

Run: `php tests/global-hierarchy-model-contract.php`
Expected: FAIL.

- [ ] **Step 3: Implement `HierarchyModel`**

Load matching organizations once, construct `id => node` and `parent_id => children` maps in memory, use explicit visited/active sets for cycle protection, compute child counts without N+1 queries, and include ancestor context when search narrows results. Normalize type/structure/operational/roots-only filters.

- [ ] **Step 4: Write failing UI contract and implement view/template**

Desktop/tablet: indented tree/list. Mobile: cards with capped visual indentation. Each node shows name/code/type/structure/status/child count/diagnostic badge/Apri. Diagnostics are warnings only and expose no repair button.

- [ ] **Step 5: Run hierarchy tests and existing organization hierarchy/list tests**

Run the two new hierarchy contracts plus current organization list/hierarchy regression tests.
Expected: PASS.

- [ ] **Step 6: Commit**

Commit message: `feat: add global organization hierarchy view`

---

### Task 6: Global Organi view with aggregated appointment counts

**Files:**
- Create: `component/admin/src/Model/BodiesModel.php`
- Create: `component/admin/src/View/Bodies/HtmlView.php`
- Create: `component/admin/tmpl/bodies/default.php`
- Modify: language files from Task 1
- Create: `tests/global-bodies-model-contract.php`
- Create: `tests/global-bodies-ui-contract.php`

**Interfaces:**
- Consumes: bodies, parent body, organization, appointments, `OrganizationBodyDomain`.
- Produces: body rows with SQL-aggregated non-deleted appointment count and existing type/status labels.

- [ ] **Step 1: Write failing model contract**

Assert joins organization + parent body, appointment counts are aggregated in SQL (subquery/group join accepted; no per-row loop query), filters are bound, and type/status are decorated through `OrganizationBodyDomain`.

- [ ] **Step 2: Run and verify RED**

Run: `php tests/global-bodies-model-contract.php`
Expected: FAIL.

- [ ] **Step 3: Implement `BodiesModel extends ListModel`**

Support search, organization, body type and visual-status filters; preserve one result row per body.

- [ ] **Step 4: Write failing UI contract and implement view/template**

Assert columns/cards: `Organo | Organizzazione | Tipo | Organo superiore | Periodo | Incarichi | Stato | Azioni`; appointment count is present; `Apri` routes to organization Organi area; no mutation controls.

- [ ] **Step 5: Run global + existing body tests**

Run new contracts plus current body/domain contracts.
Expected: PASS.

- [ ] **Step 6: Commit**

Commit message: `feat: add global organization bodies view`

---

### Task 7: Release integration, versioning and full regression verification

**Files:**
- Modify: `VERSION`
- Modify: `component/xdecaroorganizations.xml`
- Modify: `package/pkg_organizations.xml`
- Modify: `component/media/joomla.asset.json`
- Create: `component/admin/sql/updates/mysql/1.3.0.sql`
- Modify: `updates/pkg_organizations.xml`
- Modify: `updates/pkg_xdecaroorganizations.xml`
- Modify: `.github/workflows/release.yml`
- Modify: relevant CI workflow(s) so all new contracts run
- Create: `tests/organizations-1.3.0-global-admin-views-contract.php`

**Interfaces:**
- Consumes: all five working global views and existing build/release pipeline.
- Produces: installable Organizations `1.3.0` package and updater metadata after release workflow.

- [ ] **Step 1: Write failing release integration contract**

Assert exact version `1.3.0` across VERSION, component/package manifests and asset metadata; SQL update exists but introduces no new table; all five global view files are packaged; release workflow invokes all new contracts.

- [ ] **Step 2: Run and verify RED**

Run: `php tests/organizations-1.3.0-global-admin-views-contract.php`
Expected: FAIL while files still report 1.2.23.

- [ ] **Step 3: Apply 1.3.0 version/package/update metadata changes**

Use a no-op SQL marker unless a real schema need was discovered during implementation. Do not modify backup schema semantics when no schema changed.

- [ ] **Step 4: Run every new contract and repository regression suite**

Run all five global model/UI contracts, shared menu/responsive/read-only contracts, the 1.3.0 release contract, and existing affiliation/appointment/delegation/body/organization/maintenance contracts.
Expected: all PASS with no PHP notices/warnings.

- [ ] **Step 5: Run deterministic build and inspect the ZIP**

Use the repository's existing build command/workflow. Verify the package contains all five MVC view sets, language files, shared CSS and `1.3.0.sql`, and excludes temporary/backup files.

- [ ] **Step 6: Open PR and require fresh CI evidence**

PR summary must state: five read-only global views, no new DB tables, no People hard dependency, responsive mobile cards, bounded hierarchy diagnostics. Wait for PHP validation, deterministic build, clean Joomla install and upgrade runtime checks.

- [ ] **Step 7: Manual UI acceptance before merge**

On Joomla 6.1.3 verify all five pages on desktop + 375px mobile, light + dark mode: filters, pagination, `Tutte`, long names/codes, zero-record state, filtered-empty state, Apri routing, no horizontal overflow, no mutation buttons, no Console/PHP errors.

- [ ] **Step 8: Merge and verify release**

After all checks/manual acceptance are green, merge, wait for release workflow, verify `v1.3.0`, canonical + legacy updater feeds, package SHA256 and clean/upgrade runtime on main.

- [ ] **Step 9: Commit**

Commit message before PR: `release: prepare Organizations 1.3.0`
