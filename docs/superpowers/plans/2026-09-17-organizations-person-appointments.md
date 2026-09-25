# Organizations Person Appointments Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a narrow, read-only Organizations public API that returns current and historical appointments for one People UUID.

**Architecture:** Organizations remains the sole owner of appointment storage and semantics. A new `PersonAppointmentsService` joins Organizations appointments to Organizations records, applies Joomla ACL, derives role/status through `AppointmentDomain`, and exposes the data through a versioned Core capability and component accessor. People consumes this API later; no cross-component table reads are introduced.

**Tech Stack:** Joomla 6.1.3, PHP 8.3 production target, Joomla Database API, Joomla ACL, xdecaro Core CapabilityRegistry, GitHub Actions.

**Spec:** `docs/superpowers/specs/2026-09-17-people-organizations-appointments-design.md`

## Global Constraints

- Joomla 6 only.
- Target release: Organizations 1.0.27; do not modify the already manually tested 1.0.26 release candidate.
- Canonical package remains `pkg_organizations`; component remains `com_xdecaroorganizations`.
- Organizations owns `#__xdecaroorganizations_appointments`; consumers must never query this table directly.
- Public history access is read-only and guarded by `organizations.view_appointments` or `core.admin`.
- Do not expose `notes`, `end_note`, fiscal fields, address fields, or unrelated sensitive organization data.
- Current/history classification and role labels must reuse `AppointmentDomain`.

---

### Task 1: Lock the public contract with a failing test

**Files:**
- Create: `tests/person-appointments-provider-contract.php`
- Modify: `.github/workflows/build.yml`

**Interfaces:**
- Produces: required capability `organizations.people_appointments` version `1`, accessor `getPersonAppointmentsService()`, service method `getAppointmentsByPersonUuid(string $personUuid): array`, ACL action `organizations.view_appointments`.

- [ ] **Step 1: Write the failing contract test**

Create a source contract that loads `CoreIntegrationService.php`, `OrganizationsComponent.php`, `provider.php`, `access.xml`, and the expected new service path. Require these exact markers:

```php
assertContains("organizations.people_appointments", $core, 'Missing person-appointments capability.');
assertContains("getPersonAppointmentsService", $component, 'Missing component accessor.');
assertContains("organizations.view_appointments", $access, 'Missing narrow appointment-history ACL.');
assertContains("getAppointmentsByPersonUuid", $service, 'Missing person appointment lookup.');
assertContains("AppointmentDomain::status", $service, 'Status must reuse Organizations domain logic.');
assertContains("AppointmentDomain::roleLabelKey", $service, 'Role labels must reuse Organizations domain logic.');
```

Also fail if production code in the new service contains `notes`, `end_note`, `vat_id`, `tax_identifier`, `address_line`, `postal_code`, or `pec_email` in its selected/returned field allowlist.

- [ ] **Step 2: Run the test and verify RED**

Run:

```bash
php tests/person-appointments-provider-contract.php
```

Expected: FAIL because `PersonAppointmentsService.php`, the capability, accessor, and ACL do not exist yet.

- [ ] **Step 3: Add the test to CI**

Append:

```bash
php tests/person-appointments-provider-contract.php
```

to the existing Organizations source validation test sequence in `.github/workflows/build.yml`.

- [ ] **Step 4: Commit the RED test**

```bash
git add tests/person-appointments-provider-contract.php .github/workflows/build.yml
git commit -m "test: define Organizations person appointments contract"
```

### Task 2: Implement the read-only Organizations service and ACL

**Files:**
- Create: `component/admin/src/Service/PersonAppointmentsService.php`
- Modify: `component/admin/src/Service/CoreIntegrationService.php`
- Modify: `component/admin/src/Extension/OrganizationsComponent.php`
- Modify: `component/admin/services/provider.php`
- Modify: `component/admin/access.xml`
- Modify: `component/admin/language/it-IT/com_xdecaroorganizations.ini`
- Modify: `component/admin/language/en-GB/com_xdecaroorganizations.ini`
- Test: `tests/person-appointments-provider-contract.php`

**Interfaces:**
- Produces: `PersonAppointmentsService::getAppointmentsByPersonUuid(string $personUuid): array`.
- Produces: `OrganizationsComponent::getPersonAppointmentsService(): PersonAppointmentsService`.
- Produces: Core capability `organizations.people_appointments` version `1`.

- [ ] **Step 1: Implement `PersonAppointmentsService`**

Use constructor injection:

```php
public function __construct(private DatabaseInterface $db) {}
```

Validate UUID using the same canonical UUID regex already used by appointment validation. Empty/invalid UUID returns an empty array rather than broadening the query.

Authorise before querying:

```php
$user = Factory::getApplication()->getIdentity();
if (
    !$user->authorise('organizations.view_appointments', CoreIntegrationService::COMPONENT)
    && !$user->authorise('core.admin', CoreIntegrationService::COMPONENT)
) {
    throw new RuntimeException('Not authorised to query organization appointments.', 403);
}
```

Query `#__xdecaroorganizations_appointments AS a` joined to `#__xdecaroorganizations_organizations AS o` on `o.id = a.organization_id`, requiring `a.state >= 0` and `o.state >= 0`, filtered by bound `a.person_uuid = :personUuid`.

Select only:

```text
a.id, a.uuid, a.organization_id, a.role_code, a.role_custom,
a.starts_on, a.planned_ends_on, a.ended_on, a.end_reason,
o.uuid AS organization_uuid, o.name AS organization_name
```

Map each row to the exact public keys:

```php
$status = AppointmentDomain::status($row);
return [
    'appointment_id' => (int) $row['id'],
    'appointment_uuid' => (string) $row['uuid'],
    'organization_id' => (int) $row['organization_id'],
    'organization_uuid' => (string) $row['organization_uuid'],
    'organization_name' => (string) $row['organization_name'],
    'role_code' => (string) $row['role_code'],
    'role_custom' => (string) ($row['role_custom'] ?? ''),
    'role_label_key' => AppointmentDomain::roleLabelKey((string) $row['role_code']),
    'starts_on' => (string) $row['starts_on'],
    'planned_ends_on' => (string) ($row['planned_ends_on'] ?? ''),
    'ended_on' => (string) ($row['ended_on'] ?? ''),
    'end_reason' => (string) ($row['end_reason'] ?? ''),
    'visual_status' => $status,
    'is_current' => $status === 'active',
];
```

Sort current first, then newest `starts_on` first, using SQL ordering that remains deterministic.

- [ ] **Step 2: Register the capability**

In `CoreIntegrationService::registerCapabilities()` add:

```php
new Capability(self::COMPONENT, 'organizations.people_appointments', '1')
```

without changing existing capabilities.

- [ ] **Step 3: Wire the service through Joomla DI and the component**

In `provider.php` share `PersonAppointmentsService` with `DatabaseInterface`; inject it into `OrganizationsComponent` via a setter.

Add to `OrganizationsComponent`:

```php
private ?PersonAppointmentsService $personAppointments = null;

public function setPersonAppointmentsService(PersonAppointmentsService $service): void
{
    $this->personAppointments = $service;
}

public function getPersonAppointmentsService(): PersonAppointmentsService
{
    if (!$this->personAppointments) {
        throw new RuntimeException('Organizations person appointments service not initialized.');
    }
    return $this->personAppointments;
}
```

- [ ] **Step 4: Add the narrow ACL action and labels**

In `access.xml` add:

```xml
<action name="organizations.view_appointments" title="COM_XDECAROORGANIZATIONS_ACTION_VIEW_APPOINTMENTS"/>
```

Italian label:

```ini
COM_XDECAROORGANIZATIONS_ACTION_VIEW_APPOINTMENTS="Visualizza incarichi delle persone"
```

English label:

```ini
COM_XDECAROORGANIZATIONS_ACTION_VIEW_APPOINTMENTS="View people appointments"
```

- [ ] **Step 5: Run the contract test and verify GREEN**

```bash
php tests/person-appointments-provider-contract.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add component/admin/src/Service/PersonAppointmentsService.php component/admin/src/Service/CoreIntegrationService.php component/admin/src/Extension/OrganizationsComponent.php component/admin/services/provider.php component/admin/access.xml component/admin/language tests/person-appointments-provider-contract.php
git commit -m "feat: expose person appointment history provider"
```

### Task 3: Verify service semantics in real Joomla

**Files:**
- Create: `.github/workflows/person-appointments-runtime.yml`
- Test: real Joomla 6.1.3 runtime

**Interfaces:**
- Consumes: `getAppointmentsByPersonUuid(string): array`.
- Produces: runtime proof for current/history classification, field allowlist, and ACL behaviour.

- [ ] **Step 1: Build the runtime workflow**

Install Joomla 6.1.3, verified Core dependency, and current Organizations package. Seed one organization and three appointment rows for one valid `person_uuid`:

```text
current: role councillor, starts_on 2026-09-14, planned_ends_on 2027-09-17, no end reason
ended: role secretary, starts_on 2024-01-01, ended_on 2025-01-01, end_reason term_end
other person: must never be returned
```

Grant/execute as Super User so `core.admin` satisfies the ACL.

- [ ] **Step 2: Probe the public component service**

Boot `com_xdecaroorganizations`, call:

```php
$rows = $component->getPersonAppointmentsService()->getAppointmentsByPersonUuid($personUuid);
```

Assert exactly two rows, one with `is_current === true` and `visual_status === 'active'`, one with `is_current === false` and `visual_status === 'ended'`.

Assert the returned key set is exactly the approved 14-key allowlist and contains no `notes` or organization sensitive fields.

- [ ] **Step 3: Run the workflow and fix only service/runtime defects until GREEN**

Expected: Joomla runtime job exits 0 with explicit output `Organizations person appointments runtime OK`.

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/person-appointments-runtime.yml
git commit -m "test: verify Organizations person appointments runtime"
```

### Task 4: Prepare Organizations 1.0.27 without changing package identity

**Files:**
- Modify: `VERSION`
- Modify: `component/xdecaroorganizations.xml`
- Modify: `component/media/joomla.asset.json`
- Modify: `package/pkg_organizations.xml`
- Modify: `updates/pkg_organizations.xml`
- Modify: `updates/pkg_xdecaroorganizations.xml`
- Modify: `.github/workflows/release.yml`
- Modify: `README.md`

**Interfaces:**
- Keeps: `pkg_organizations`, `com_xdecaroorganizations`.
- Publishes: Organizations 1.0.27.

- [ ] **Step 1: Set every source version to `1.0.27`**

Keep the existing package migration logic untouched. Do not reintroduce `pkg_xdecaroorganizations` as the canonical package.

- [ ] **Step 2: Update release notes**

Release notes must state that 1.0.27 adds the read-only `organizations.people_appointments` capability and `organizations.view_appointments`, without exposing appointment notes or organization sensitive fields.

Both canonical and legacy updater feeds must point to `pkg_organizations_1.0.27.zip` after publication.

- [ ] **Step 3: Run deterministic build twice**

```bash
chmod +x build/build.sh
build/build.sh
cp dist/SHA256SUMS.txt /tmp/org-1027-sums.txt
build/build.sh
diff -u /tmp/org-1027-sums.txt dist/SHA256SUMS.txt
unzip -t dist/pkg_organizations_1.0.27.zip
```

Expected: no diff and archive test passes.

- [ ] **Step 4: Commit**

```bash
git add VERSION component package updates .github/workflows/release.yml README.md
git commit -m "release: prepare Organizations 1.0.27"
```

### Task 5: Final Organizations verification gate

**Files:** no production changes unless a verified defect is found.

- [ ] **Step 1: Run source tests**

```bash
find component package tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/person-appointments-provider-contract.php
```

Expected: all PHP syntax checks and contract pass.

- [ ] **Step 2: Run the complete existing Organizations CI and the new runtime workflow**

Required green evidence:
- PHP 8.1/8.3 source validation already retained by repository CI;
- deterministic build;
- Joomla clean install;
- 1.0.25 → canonical package upgrade regression;
- appointment lifecycle regression;
- new person-appointments runtime.

- [ ] **Step 3: Review the diff against the spec**

Confirm no People table access was introduced, no notes/sensitive organization fields are exposed, and no mutation method exists in the new service.

- [ ] **Step 4: Leave the PR Draft until People 1.3.2 cross-repository runtime is green**

Do not merge or publish 1.0.27 before the People consumer proves the public contract end to end.